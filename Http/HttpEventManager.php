<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Http\HttpHeader;
use com\github\tncrazvan\CatServer\Cat;

abstract class HttpEventManager extends EventManager{
    protected 
            $default_header=true,
            $content;
    public function __construct($client,HttpHeader &$client_header,string &$content) {
        parent::__construct($client,$client_header);
        $this->content=$content;
    }
    
    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }
    
    /**
     * Execute the current event.
     * 
     * @return void This method is invoked once by the server to trigger all the required components 
     * in order to reply to the http request
     * such as the appropriate http header and requested file or controller.
     * 
     * Be aware that missuse of this method could lead to request loops.
     */
    public function run(){
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
        exit;
    }
    
    protected abstract function &onControllerRequest(string &$location);
    /**
     * Get user languages from the request header.
     * @return &array
     */
    
    
    private $first_message = true;
    private function sendHeader():void{
        $this->first_message=false;
        socket_write($this->client, $this->server_header->toString()."\r\n");
        $this->alive=true;
    }
    
    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    public function send(string $data=null):int{
        if($this->alive){
            if($this->first_message && $this->default_header){
                $this->sendHeader();
            }
            try{
                return socket_write($this->client, $data);
            } catch (Exception $ex) {
                return -1;
            }
            
        }
    }
    
    /**
     * Set the Content-Type field to the response header.
     * @param string $type content type string, such as "text/plain", "text/html" ecc...
     */
    public function setContentType(string $type):void{
        $this->setHeaderField("Content-Type", $type);
    }
    
    /**
     * Send contents of a file to the user.
     * @param array $filename An array of strings containing the name of the file. 
     * The elements of this array will be joined on "/" and create a filename.
     * @return void This method manages byterange requests.
     * If the request header contains byterange fields, Content-Type will be set as 
     * "multipart/byteranges; boundary=$boundary" and the data will be sent as a byterange response, otherwise the Content-Type
     * will be determined using the Cat::resolveContentType method.
     * 
     * In both cases, regardless if the request is a byterange request or not, the method will send the data as a byterange response.
     * The response ranges will be set as specified by the request header fields 
     * or from 0 to the end of file if ranges are not found.
     */
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
