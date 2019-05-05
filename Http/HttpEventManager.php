<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Cat;

abstract class HttpEventManager extends EventManager{
    protected 
            $default_header=true,
            $alive=true,
            $client,
            $content,
            $session = null,
            $session_id = null;
    public function __construct($client,HttpHeader &$client_header,string &$content) {
        parent::__construct($client,$client_header);
        $this->content=$content;
    }
    
    /**
     * Starts a client http session.
     * @return &array This method returns an array pointer, so any changes made to the array will be saved across all http requests relative to this session, untill the server kills the session due to inactivity. The default session ttl is 24 minutes.
     */
    public function &startSession():array{
        $this->session = &HttpSessionManager::startSession($this, $this->session_id);
        return $this->session;
    }
    
    /**
     * Removes the session of the current client from the server sessions list.
     * @return void No need to call HttpEventManager::startSession, this method will 
     * call it automatically if needed.
     */
    public function stopSession():void{
        if($this->session_id === null){
            $this->startSession();
        }
        $this->session = null;
        HttpSessionManager::stopSession(HttpSessionManager::getSession($this->session_id));
    }
    
    /**
     * Checks if the current client can find a session.
     * @return bool true if the client has "session_id" cookie and its value exists in the server sessions list, otherwise false.
     */
    public function issetSession():bool{
        if($this->session === null) return false;
        return HttpSessionManager::issetSession($e);
    }
    
    /**
     * Closes the client connection.
     * @return void This method WILL NOT invoke the "onClose" method.
     */
    public function close():void{
        socket_set_block($this->client);
        socket_set_option($this->client, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        socket_close($this->client);
    }
    /**
     * Get client socket
     * @return \resource This is the socket of the client.
     */
    public function &getClient(){
        return $this->client;
    }
    
    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function setHeaderField(string $key, string $content):void{
        $this->server_header->set($key,$content);
    }
        
    /**
     * Set the status of your response.
     * @param string $status a status code. Multiple status codes can be found in the Cat class, suche as Cat::STATUS_SUCCESS.
     */
    public function setStatus(string $status):void{
        $this->setHeaderField("Status", "HTTP/1.1 $status");
    }
    
    /**
     * Get header field.
     * @param string $key name of the header field.
     * @return string value of the header field.
     */
    public function &getHeaderField(string $key):string{
        return $this->server_header->get($key);
    }
    
    /**
     * Get response header.
     * @return \com\github\tncrazvan\CatServer\Http\HttpHeader header of the your response message.
     */
    public function &getHeader():HttpHeader{
        return $this->server_header;
    }
    
    /**
     * Get request header.
     * @return \com\github\tncrazvan\CatServer\Http\HttpHeader header of the client request.
     */
    public function &getClientHeader():HttpHeader{
        return $this->client_header;
    }
    
    /**
     * Get request method.
     * @return string method of the client request.
     */
    public function &getMethod():string{
        return $this->getHeaderField("Method");
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
    public function execute():void{
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
    }
    
    protected abstract function &onControllerRequest(string &$location);
    /**
     * Get user languages from the request header.
     * @return &array
     */
    public function &getUserLanguages():array{
        return $this->user_languages;
    }
    /**
     * Get the default user language from the request header.
     * @return &string
     */
    public function &getUserDefaultLanguage():string{
        return $this->user_languages["DEFAULT-LANGUAGE"];
    }
    
    /**
     * Get the user agent of the client.
     * @return &string
     */
    public function &getUserAgent():string{
        return $this->getClientHeader()->get("User-Agent");
    }
    
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
