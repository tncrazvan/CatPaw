<?php
namespace com\github\tncrazvan\catpaw\http;
use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\tools\Status;

class HttpHeaders{
    private static string $VERSION = "HTTP/1.1";
    private array $headers = [];
    private array $cookies = [];
    private string $status = Status::SUCCESS;
    private ?string $resource = null;
    public bool $initialized = false;
    const DATE_FORMAT = "D j M Y G:i:s T";
    public function __construct(EventManager $em=null, bool $createSuccessHeader = true) {
        if($createSuccessHeader){
            $this->status = Status::SUCCESS;
            $this->headers["Date"] = date(self::DATE_FORMAT); 
            if($em !== null){
                $this->initialize($em);
            }
        }
    }

    public function &getHeadersArray():array{
        return $this->headers;
    }

    public function &getCookiesArray():array{
        return $this->cookies;
    }

    public function initialize(EventManager $em):void{
        if(!$this->initialized){
            foreach($em->getHttpEventListener()->getSharedObject()->getHeaders() as $key => &$value){
                if(!$this->has($key))
                    $this->set($key, $value);
            }
            $this->initialized = true;
        }
    }

    public function mix(HttpHeaders $headers):void{
        foreach($headers->getHeadersArray() as $key => &$value){
            if(!isset($this->headers[$key]))
                $this->headers[$key] = $value;
        }
        foreach($headers->getCookiesArray() as $key => &$value){
            if(!isset($this->cookies[$key]))
                $this->cookies[$key] = $value;
        }
    }
    
    public function fieldToString(string $key):string{
        return $key.": ".$this->headers[$key]."\r\n";
    }
    
    public function cookieToString(string $key):string{
        $cookie = $this->cookies[$key];
        return $cookie[4].": "
                .$key."=".\urlencode($cookie[0])
                .($cookie[1]===null?"":"; path=".$cookie[1])
                .($cookie[2]===null?"":"; domain=".$cookie[2])
                .($cookie[3]===null?"":"; expires=".date(self::DATE_FORMAT,$cookie[3]))."\r\n";
                
    }
    
    public function &toString():string{
        $result = '';

        if($this->status !== null)  //this should trigger only if it's a response
            $result .= self::$VERSION.' '.$this->status."\r\n";
        if($this->resource !== null) //this should trigger only if it's a request
            $result .= $this->resource."\r\n";

        foreach(array_keys($this->headers) as &$key){
            $result .= $this->fieldToString($key);
        }
        foreach(array_keys($this->cookies) as &$key){
            $result .= $this->cookieToString($key);
        }
        return $result;
    }
    
    public function has(string $key):bool{
        return isset($this->headers[$key]);
    }
    
    public function set(string $key, $content):void{
        $this->headers[$key] = $content;
    }
    
    public function setStatus(string $status):void{
        $this->status = $status;
    }

    public function setResource(string $resource):void{
        $this->resource = $resource;
    }

    public function setContentType($content):void{
        $this->set("Content-Type",$content);
    }

    public function &get(string $key){
        $result = null;
        if(!isset($this->headers[$key])) 
            return $result;
        $result = trim($this->headers[$key]);
        return $result;
    }

    public function &getStatus(){
        return $this->status;
    }

    public function &getResource(){
        return $this->resource;
    }
    
    public function issetCookie(string $key):bool{
        return isset($this->cookies[trim($key)]);
    }
    
    public function &getCookie(string $key):string{
        return $this->cookies[$key][0];
    }

    public function &getCookies():array{
        $cookies = [];
        foreach($this->cookies as $key => &$value){
            $cookies[$key] = $value[0];
        }
        return $cookies;
    }
    
    public function setCookies(array &$cookies){
        $this->cookies = $cookies;
    }

    public function setCookie(string $key, string $content, string $path="/", string $domain=null, string $expire=null):void{
        $cookie = array_fill(0, 4, null);
        $cookie[0] = $content;
        $cookie[1] = $path;
        $cookie[2] = $domain;
        $cookie[3] = $expire;
        $cookie[4] = "Set-Cookie";
        $this->cookies[trim($key)] = $cookie;
    }
    
    public static function fromString(EventManager $em=null, string &$string):HttpHeaders{
        $httpHeaders = new HttpHeaders($em,false);
        $lines = \preg_split("/\\r\\n/", $string);
        foreach($lines as &$line){
            if($line === "") continue;
            $item = \preg_split("/:\\s*/", $line, 2);
            $itemLength = \count($item);
            if($itemLength > 1){
                if($item[0] === "Cookie"){
                    $cookies= \preg_split("/;/", $item[1]);
                    foreach($cookies as &$cookie){
                        $cookie = \preg_split("/=(?!\\s|\\s|$)/",$cookie);
                        $cookieLength = count($cookie);
                        if($cookieLength > 1){
                            $content = \array_fill(0, 4, null);
                            $content[0] = \urldecode($cookie[1]);
                            $content[1] = $cookieLength>2?$cookie[2]:null;
                            $content[2] = $cookieLength>3?$cookie[3]:null;
                            $content[3] = $cookieLength>3?$cookie[3]:null;
                            $content[4] = "Cookie";
                            $httpHeaders->cookies[\trim($cookie[0])] = $content;
                        }
                    }
                }else{
                    $httpHeaders->set($item[0], $item[1]);
                }
            }else{
                if(\preg_match("/^.+(?=\\s\\/).*HTTP\\/.*\$/", $line) > 0){
                    $parts = \preg_split("/\\s+/", $line);
                    $httpHeaders->set("Method", $parts[0]);
                    $httpHeaders->setResource($parts[1]);
                    $httpHeaders->set("Version", $parts[2]);
                }else{
                    $httpHeaders->set($line, null);
                }
            }
        }
        return $httpHeaders;
    }
}
