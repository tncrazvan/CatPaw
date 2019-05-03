<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Cat;

abstract class HttpEventManager extends EventManager{
    protected 
            $default_header=true,
            $alive=true,
            $client,
            $content;
    public function __construct($client,HttpHeader &$client_header,string &$content) {
        parent::__construct($client_header);
        $this->client=$client;
        $this->content=$content;
    }
    
    public function &getAddress():string{
        socket_getpeername($this->client, $address);
        return $address;
    }
    
    public function &getPort():string{
        socket_getpeername($this->client, $address,$port);
        return $port;
    }
    
    /**
     * Note that this method WILL NOT invoke interaface method onClose
     */
    public function close():void{
        socket_set_block($this->client);
        socket_set_option($this->client, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        socket_close($this->client);
    }
    
    public function getClient(){
        return $this->client;
    }
    
    public function setHeaderField(string $key, string $content):void{
        $this->server_header->set($key,$content);
    }
        
    public function setStatus(string $status):void{
        $this->setHeaderField("Status", "HTTP/1.1 $status");
    }
    
    public function &getHeaderField(string $key):string{
        return $this->server_header->get($key);
    }
    
    public function &getHeader():HttpHeader{
        return $this->server_header;
    }
    
    public function &getClientHeader():HttpHeader{
        return $this->client_header;
    }
    
    public function &getMethod():string{
        return $this->getHeaderField("Method");
    }
    
    public function isAlive():bool{
        return $this->alive;
    }
    
    public function execute():bool{
        $this->findUserLanguages();
        $filename = Cat::$web_root.$this->location;
        if(file_exists($filename)){
            if(!is_dir($filename)){
                $last_modified=filemtime($filename);
                $this->setHeaderField("Last-Modified", date(Cat::DATE_FORMAT, $last_modified));
                $this->setHeaderField("Last-Timestamp", $last_modified);
                $this->setContentType(Cat::getContentType($filename));
                $this->sendFileContents($filename);
            }else{
                $this->send($this->onControllerRequest($this->location));
            }
        }else{
            $this->send($this->onControllerRequest($this->location));
        }
        $this->close();
        return true;
    }
    
    protected abstract function &onControllerRequest(string &$location);
    
    public function &getUserLanguages():array{
        return $this->user_languages;
    }
    
    public function &getUserDefaultLanguage():string{
        return $this->user_languages["DEFAULT-LANGUAGE"];
    }
    
    public function &getUserAgent():string{
        return $this->getClientHeader()->get("User-Agent");
    }
    
    private $first_message = true;
    public function sendHeader():void{
        $this->first_message=false;
        socket_write($this->client, $this->server_header->toString()."\r\n");
        $this->alive=true;
    }
    
    public function send(string $data=null):int{
        if($this->alive){
            if($this->first_message && $this->default_header){
                $this->sendHeader();
            }
            try{
                return socket_write($this->client, $data);
            } catch (Exception $ex) {
                return 0;
            }
            
        }
    }
    
    public function setContentType(string $type):void{
        $this->setHeaderField("Content-Type", $type);
    }
    
    
    public function sendFileContents(string ...$filename):void{
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
        
        if($this->client_header->has("Range")){
            $this->setStatus(Cat::STATUS_PARTIAL_CONTENT);
            $ranges = preg_split("/=/",preg_split("/,/",$this->client_header->get("Range")));
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
            $ctype = Cat::getContentType($filename);
            $start;
            $end;
            $range_start_length = count($range_start);
            if($range_start_length > 1){
                $body = "";
                $boundary = Cat::generateMultipartBoundary();
                if($this->first_message){
                    $this->first_message=false;
                    $this->setContentType("multipart/byteranges; boundary=$boundary");
                    socket_write($this->client, $this->server_header->to_string());
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
                if($this->first_message && $this->default_header){
                    $this->first_message=false;
                    $this->setHeaderField("Content-Range", "bytes $start-$end/$file_length");
                    $this->setHeaderField("Content-Length", "$length");
                    socket_write($this->client, $this->server_header->to_string()."\r\n");
                }
                fseek($raf, $start);
                $buffer = fread($raf,$end-$start+1);
                socket_write($this->client, $buffer);
            }
        }else{
            $this->setHeaderField("Content-Length", $file_length);
            fseek($raf, 0);
            $buffer = fread($raf, $file_length);
            $this->send($buffer);
        }
        fclose($raf);
    }
}
