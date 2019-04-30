<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Cat;

abstract class HttpEventManager extends EventManager{
    protected 
            $default_headers=true,
            $alive=true,
            $client,
            $content;
    public function __construct($client,HttpHeader &$client_headers,string &$content) {
        parent::__construct($client_headers);
        $this->client=$client;
        $this->content=$content;
    }
    
    /**
     * Note that this method WILL NOT invoke interaface method onClose
     */
    public function close():void{
        socket_set_block($this->client);
        socket_set_option($this->client, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        socket_close($this->client);
    }
    
    public function get_client(){
        return $this->client;
    }
    
    public function set_headers_field(string $key, string $content):void{
        $this->server_headers->set($key,$content);
    }
        
    public function set_status(string $status):void{
        $this->set_headers_field("Status", "HTTP/1.1 $status");
    }
    
    public function get_headers_field(string $key):string{
        return $this->server_headers->get($key);
    }
    
    public function get_headers():HttpHeader{
        return $this->server_headers;
    }
    
    public function get_client_headers():HttpHeader{
        return $this->client_headers;
    }
    
    public function get_method():string{
        return $this->client_headers->get("Method");
    }
    
    public function is_alive():bool{
        return $this->alive;
    }
    
    public function execute():bool{
        $this->find_user_languages();
        $filename = Cat::$web_root.$this->location;
        if(file_exists($filename)){
            if(!is_dir($filename)){
                $last_modified=filemtime($filename);
                $this->set_headers_field("Last-Modified", date(Cat::DATE_FORMAT, $last_modified));
                $this->set_headers_field("Last-Timestamp", $last_modified);
                $this->set_content_type(Cat::get_content_type($filename));
                $this->send_file_contents($filename);
            }else{
                $this->send($this->on_controller_request($this->location));
            }
        }else{
            $this->send($this->on_controller_request($this->location));
        }
        $this->close();
        return true;
    }
    
    protected abstract function on_controller_request(string $location);
    
    public function get_user_languages():array{
        return $this->user_languages;
    }
    
    public function get_user_default_language():string{
        return $this->user_languages["DEFAULT-LANGUAGE"];
    }
    
    public function get_user_agent():string{
        return $this->get_client_headers()->get("User-Agent");
    }
    
    private $first_message = true;
    public function send_headers():void{
        $this->first_message=false;
        socket_write($this->client, $this->server_headers->to_string()."\r\n");
        $this->alive=true;
    }
    
    public function send(string $data=null):int{
        if($this->alive){
            if($this->first_message && $this->default_headers){
                $this->send_headers();
            }
            return socket_write($this->client, $data);
        }
    }
    
    public function set_content_type(string $type):void{
        $this->set_headers_field("Content-Type", $type);
    }
    
    
    public function send_file_contents(string ...$filename):void{
        $filename_length = count($filename);
        if($filename_length === 0) return;
        $buffer;
        if($filename_length === 1){
            $filename = join("/",[Cat::$web_root,$filename[0]]);
        }else{
            $filename = join("/",$filename);
        }
        
        $raf = fopen($filename,"r");
        $file_length = filesize($filename);
        
        if($this->client_headers->is_defined("Range")){
            $this->set_status(Cat::STATUS_PARTIAL_CONTENT);
            $ranges = preg_split("/=/",preg_split("/,/",$this->client_headers->get("Range")));
            $ranges_length = count($ranges);
            $range_start = array_fill(0, $ranges_length, null);
            $range_end = array_fill(0, $ranges_length, null);
            $last_index;
            for($i = 0; $i < $ranges_length; $i++){
                $last_index = strlen($ranges[$i])-1;
                $tmp = preg_split("/-/",$ranges[$i]);
                if(substr($ranges[$i], 0, 1) === "-"){
                    $range_start[$i] = intval($tmp[0]);
                }else{
                    $range_start[$i] = 0;
                }
                
                if(!substr($ranges[i], $last_index,$last_index+1) === "-"){
                    $range_end[$i] = intval($tmp[1]);
                }else{
                    $range_end[$i] = $file_length-1;
                }
            }
            $ctype = Cat::get_content_type($filename);
            $start;
            $end;
            $range_start_length = count($range_start);
            if($range_start_length > 1){
                $body = "";
                $boundary = Cat::generateMultipartBoundary();
                if($this->first_message){
                    $this->first_message=false;
                    $this->set_content_type("multipart/byteranges; boundary=$boundary");
                    socket_write($this->client, $this->server_headers->to_string());
                }
                
                for($i = 0; $i < $range_start_length; $i++){
                    $start = $range_start[$i];
                    $end = $range_end[$i];
                    socket_write($this->client, "--$boundary\r\n");
                    socket_write($this->client, "Content-Type: $ctype\r\n");
                    socket_write($this->client, "Content-Range: bytes $start-$end/$file_length\r\n\r\n");
                
                    if($end-$start+1 > Cat::$http_mtu){
                        $remaining_bytes = $end-$start+1;
                        $buffer = "";
                        $read_length = Cat::$http_mtu;
                        fseek($raf, $start);
                        while($remaining_bytes > 0){
                            $buffer = fread($raf, $read_length);
                            socket_write($this->client, $buffer);
                            $remaining_bytes -= Cat::$http_mtu;
                            if($remaining_bytes < 0){
                                $read_length = $remaining_bytes+Cat::$http_mtu;
                                $remaining_bytes = 0;
                            }
                        }
                    }else{
                        fseek($raf, $start);
                        $buffer = fread($raf, $end-$start+1);
                        socket_write($this->client, $buffer);
                    }
                    if($i > $range_start_length-1){
                        socket_write($this->client, "\r\n");
                    }
                }
                if($range_start_length > 1){
                    socket_write($this->client, "\r\n--$boundary--");
                }
            }else{
                $start = $range_start[0];
                $end = $range_end[0];
                $len = $end-$start+1;
                if($this->first_message && $this->default_headers){
                    $this->first_message=false;
                    $this->set_headers_field("Content-Range", "bytes $start-$end/$file_length");
                    $this->set_headers_field("Content-Length", "$length");
                    socket_write($this->client, $this->server_headers->to_string()."\r\n");
                }
                fseek($raf, $start);
                $buffer = fread($raf,$end-$start+1);
                socket_write($this->client, $buffer);
            }
        }else{
            $this->set_headers_field("Content-Length", $file_length);
            fseek($raf, 0);
            $buffer = fread($raf, $file_length);
            $this->send($buffer);
        }
        fclose($raf);
    }
}
