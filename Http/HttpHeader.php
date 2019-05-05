<?php
namespace com\github\tncrazvan\CatServer\Http;

class HttpHeader{
    private $header = [],$cookies = [];
    const DATE_FORMAT = "D j M Y G:i:s T";
    public function __construct(bool $create_success_header = true) {
        if($create_success_header){
            $this->header["Status"] = "HTTP/1.1 200 OK";
            $this->header["Date"] = date(self::DATE_FORMAT); 
        }
    }
    
    public function fieldToString(string $key):string{
        if($key === "Resource" || $key === "Status"){
            return $this->header[$key]."\r\n";
        }
        return $key.": ".$this->header[$key]."\r\n";
    }
    
    public function cookieToString(string $key):string{
        $cookie = $this->cookies[$key];
        return $cookie[4].": "
                .$key."=".$cookie[0]
                .($cookie[1]===null?"":"; path=".$cookie[1])
                .($cookie[2]===null?"":"; domain=".$cookie[2])
                .($cookie[3]===null?"":"; expires=".date(self::DATE_FORMAT,$cookie[3]))."\r\n";
    }
    
    public function toString():string{
        $result = "";
        foreach(array_keys($this->header) as &$key){
            $result .= $this->fieldToString($key);
        }
        foreach(array_keys($this->cookies) as &$key){
            $result .= $this->cookieToString($key);
        }
        return $result;
    }
    
    public function has(string $key):bool{
        return isset($this->header[$key]);
    }
    
    public function set(string $key, string $content):void{
        $this->header[$key] = $content;
    }
    
    public function get(string $key){
        if(!isset($this->header[$key])) return null;
        return trim($this->header[$key]);
    }
    
    public function issetCookie(string $key):bool{
        return isset($this->cookies[trim($key)]);
    }
    
    public function &getCookie(string $key):string{
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
    
    public static function fromString(string &$string):HttpHeader{
        $http_header = new HttpHeader();
        $lines = preg_split("/\\r\\n/", $string);
        foreach($lines as &$line){
            if($line === "") continue;
            $item = preg_split("/:\\s*/", $line, 2);
            $item_length = count($item);
            if($item_length > 1){
                if($item[0] === "Cookie"){
                    $cookies= preg_split("/;/", $item[1]);
                    foreach($cookies as &$cookie){
                        $cookie = preg_split("/=(?!\\s|\\s|$)/",$cookie);
                        $cookie_length = count($cookie);
                        if($cookie_length > 1){
                            $content = array_fill(0, 4, null);
                            $content[0] = $cookie[1];
                            $content[1] = $cookie_length>2?$cookie[2]:null;
                            $content[2] = $cookie_length>3?$cookie[3]:null;
                            $content[3] = $cookie_length>3?$cookie[3]:null;
                            $content[4] = "Cookie";
                            $http_header->cookies[trim($cookie[0])] = $content;
                        }
                    }
                }else{
                    $http_header->set($item[0], $item[1]);
                }
            }else{
                if(preg_match("/^.+(?=\\s\\/).*HTTPS?\\/.*\$/", $line) > 0){
                    $parts = preg_split("/\\s+/", $line);
                    $http_header->set("Method", $parts[0]);
                    $http_header->set("Resource", $parts[1]);
                    $http_header->set("Version", $parts[2]);
                }else{
                    $http_header->set($line, null);
                }
            }
        }
        return $http_header;
    }
}
