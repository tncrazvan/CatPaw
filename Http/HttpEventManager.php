<?php
namespace com\github\tncrazvan\CatPaw\Http;

use Exception;
use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Mime;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\CatPaw\Http\EventManager;

abstract class HttpEventManager extends EventManager{
    protected 
            $defaultHeader=true,
            $content;
    public function __construct($client,HttpHeader &$clientHeader,string &$content) {
        parent::__construct($client,$clientHeader);
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
        $filename = G::$webRoot."/".$this->location;
        if(file_exists($filename)){
            if(!is_dir($filename)){
                $lastModified=filemtime($filename);
                $this->setHeaderField("Last-Modified", date(Strings::DATE_FORMAT, $lastModified));
                $this->setHeaderField("Last-Timestamp", $lastModified);
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
    
    private $firstMessage = true;
    private function sendHeader():void{
        $this->firstMessage=false;
        $string = $this->serverHeader->toString()."\r\n";
        @fwrite($this->client, $string,strlen($string));
        $this->alive=true;
    }
    
    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    public function send(string $data=null):int{
        if($this->alive){
            if($this->firstMessage && $this->defaultHeader){
                $this->sendHeader();
            }
            try{
                return @fwrite($this->client, $data, strlen($data));
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
     * will be determined using the Mime::getContentType method.
     * 
     * In both cases, regardless if the request is a byterange request or not, the method will send the data as a byterange response.
     * The response ranges will be set as specified by the request header fields 
     * or from 0 to the end of file if ranges are not found.
     */
    public function sendFileContents(string ...$filename):void{
        $filenameLength = count($filename);
        if($filenameLength === 0) return;
        $buffer = "";
        $filename = preg_replace('#/+#','/',filter_var(join("/",$filename), FILTER_SANITIZE_URL));
        $filesize = filesize($filename);
        $raf = fopen($filename,"r");
        $fileLength = filesize($filename);
        $ctype = Mime::getContentType($filename);
        
        if($this->clientHeader->has("Range")){
            $this->setStatus(G::STATUS_PARTIAL_CONTENT);
            $ranges = preg_split("/,/",preg_split("/=/",$this->clientHeader->get("Range"))[1]);
            $rangesLength = count($ranges);
            $rangeStart = array_fill(0, $rangesLength, null);
            $rangeEnd = array_fill(0, $rangesLength, null);
            $lastIndex;
            for($i = 0; $i < $rangesLength; $i++){
                $lastIndex = strlen($ranges[$i])-1;
                $tmp = preg_split("/-/",$ranges[$i]);
                if(substr($ranges[$i], 0, 1) === "-"){
                    $rangeStart[$i] = intval($tmp[0]);
                }else{
                    $rangeStart[$i] = 0;
                }
                if(!substr($ranges[$i], $lastIndex,$lastIndex+1) === "-"){
                    $rangeEnd[$i] = intval($tmp[1]);
                }else{
                    $rangeEnd[$i] = $fileLength-1;
                }
            }
            $start;
            $end;
            $rangeStartLength = count($rangeStart);
            if($rangeStartLength > 1){
                $body = "";
                $boundary = G::generateMultipartBoundary();
                if($this->firstMessage){
                    $this->firstMessage=false;
                    $this->setContentType("multipart/byteranges; boundary=$boundary");
                    $string = $this->serverHeader->toString();
                    fwrite($this->client, $string, strlen($string));
                }
                
                for($i = 0; $i < $rangeStartLength; $i++){
                    $start = $rangeStart[$i];
                    $end = $rangeEnd[$i];
                    if($filesize-1 < $start){
                        continue;
                    }
                    if($filesize-1 < $end){
                        $end = $filesize-1;
                    }

                    $startConnectionStr = "--$boundary\r\n";
                    $startConnectionStr .= "Content-Type: $ctype\r\n";
                    $startConnectionStr .= "Content-Range: bytes $start-$end/$fileLength\r\n\r\n";

                    fwrite($this->client, $startConnectionStr, strlen($startConnectionStr));
                
                    if($end-$start+1 > G::$httpMtu){
                        $remainingBytes = $end-$start+1;
                        $buffer = "";
                        $readLength = G::$httpMtu;
                        fseek($raf, $start);
                        while($remainingBytes > 0){
                            $buffer = fread($raf, $readLength);
                            fwrite($this->client, $buffer, strlen($buffer));
                            $remainingBytes -= G::$httpMtu;
                            if($remainingBytes < 0){
                                $readLength = $remainingBytes+G::$httpMtu;
                                $remainingBytes = 0;
                            }
                        }
                    }else{
                        fseek($raf, $start);
                        $buffer = fread($raf, $end-$start+1);
                        fwrite($this->client, $buffer, strlen($buffer));
                    }
                    if($i > $rangeStartLength-1){
                        fwrite($this->client, "\r\n",2);
                    }
                }
                $endConnectionStr = "\r\n--$boundary--";
                fwrite($this->client, $endConnectionStr, strlen($endConnectionStr));
            }else{
                $start = $rangeStart[0];
                $end = $rangeEnd[0];
                if($filesize-1 > $start){
                    if($filesize-1 < $end){
                        $end = $filesize-1;
                    }
                    $len = $end-$start+1;
                    $this->setHeaderField("Content-Range", "bytes $start-$end/$fileLength");
                    $this->setHeaderField("Content-Length", $len);
                    fseek($raf, $start);
                    $buffer = fread($raf,$end-$start+1);
                }
                $this->send($buffer);
            }
        }else{
            $this->setContentType($ctype);
            $this->setHeaderField("Content-Length", $fileLength);
            if($filesize > 0){
                fseek($raf, 0);
                $buffer = fread($raf, $fileLength);
            }
            $this->send($buffer);
        }
        fclose($raf);
    }
}
