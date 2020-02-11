<?php
namespace com\github\tncrazvan\catpaw\http;
class HttpHeaders{
    private $headers = [], $cookies = [];
    public $initialized = false;
    const DATE_FORMAT = "D j M Y G:i:s T";
    public function __construct(EventManager $em=null, bool $createSuccessHeader = true) {
        if($createSuccessHeader){
            $this->headers["Status"] = "HTTP/1.1 200 OK";
            $this->headers["Date"] = date(self::DATE_FORMAT); 
            if($em !== null){
                $this->initialize($em);
            }
        }
    }

    public function &getHeadersAray():array{
        return $this->headers;
    }

    public function &getCookiesAray():array{
        return $this->cookies;
    }

    public function initialize(EventManager $em):void{
        if(!$this->initialized){
            foreach($em->listener->so->headers as $key => &$value){
                $this->set($key, $value);
            }
            $this->initialized = true;
        }
    }

    public function mix(HttpHeaders $headers):void{
        foreach($headers->getHeadersAray() as $key => &$value){
            $this->headers[$key] = $value;
        }
        foreach($headers->getCookiesAray() as $key => &$value){
            $this->cookies[$key] = $value;
        }
    }
    
    public function fieldToString(string $key):string{
        if($key === "Resource" || $key === "Status"){
            return $this->headers[$key]."\r\n";
        }
        return $key.": ".$this->headers[$key]."\r\n";
    }
    
    public function cookieToString(string $key):string{
        $cookie = $this->cookies[$key];
        return $cookie[4].": "
                .$key."=".$cookie[0]
                .($cookie[1]===null?"":"; path=".$cookie[1])
                .($cookie[2]===null?"":"; domain=".$cookie[2])
                .($cookie[3]===null?"":"; expires=".date(self::DATE_FORMAT,$cookie[3]))."\r\n";
    }
    
    public function &toString():string{
        $result = "";
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
    
    public function set(string $key, string $content):void{
        $this->headers[$key] = $content;
    }
    
    public function setStatus($content):void{
        $this->set("Status",$content);
    }

    public function setContentType($content):void{
        $this->set("Content-Type",$content);
    }

    public function get(string $key):string{
        if(!isset($this->headers[$key])) return null;
        return trim($this->headers[$key]);
    }
    
    public function issetCookie(string $key):bool{
        return isset($this->cookies[trim($key)]);
    }
    
    public function getCookie(string $key):string{
        return urldecode($this->cookies[$key][0]);
    }
    
    public function setCookie(string $key, string $content, string $path="/", string $domain=null, string $expire=null):void{
        $cookie = array_fill(0, 4, null);
        $cookie[0] = urlencode($content);
        $cookie[1] = $path;
        $cookie[2] = $domain;
        $cookie[3] = $expire;
        $cookie[4] = "Set-Cookie";
        $this->cookies[trim($key)] = $cookie;
    }
    
    public static function fromString(EventManager $em=null, string &$string):HttpHeaders{
        $httpHeaders = new HttpHeaders($em,false);
        $lines = preg_split("/\\r\\n/", $string);
        foreach($lines as &$line){
            if($line === "") continue;
            $item = preg_split("/:\\s*/", $line, 2);
            $itemLength = count($item);
            if($itemLength > 1){
                if($item[0] === "Cookie"){
                    $cookies= preg_split("/;/", $item[1]);
                    foreach($cookies as &$cookie){
                        $cookie = preg_split("/=(?!\\s|\\s|$)/",$cookie);
                        $cookieLength = count($cookie);
                        if($cookieLength > 1){
                            $content = array_fill(0, 4, null);
                            $content[0] = $cookie[1];
                            $content[1] = $cookieLength>2?$cookie[2]:null;
                            $content[2] = $cookieLength>3?$cookie[3]:null;
                            $content[3] = $cookieLength>3?$cookie[3]:null;
                            $content[4] = "Cookie";
                            $httpHeaders->cookies[trim($cookie[0])] = $content;
                        }
                    }
                }else{
                    $httpHeaders->set($item[0], $item[1]);
                }
            }else{
                if(preg_match("/^.+(?=\\s\\/).*HTTP\\/.*\$/", $line) > 0){
                    $parts = preg_split("/\\s+/", $line);
                    $httpHeaders->set("Method", $parts[0]);
                    $httpHeaders->set("Resource", $parts[1]);
                    $httpHeaders->set("Version", $parts[2]);
                }else{
                    $httpHeaders->set($line, null);
                }
            }
        }
        return $httpHeaders;
    }
}
