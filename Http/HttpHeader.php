<?php
namespace com\github\tncrazvan\CatPaw\Http;

class HttpHeader{
    private $header = [],$cookies = [];
    const DATE_FORMAT = "D j M Y G:i:s T";
    public function __construct(bool $createSuccessHeader = true) {
        if($createSuccessHeader){
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
        $httpHeader = new HttpHeader();
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
                            $httpHeader->cookies[trim($cookie[0])] = $content;
                        }
                    }
                }else{
                    $httpHeader->set($item[0], $item[1]);
                }
            }else{
                if(preg_match("/^.+(?=\\s\\/).*HTTP\\/.*\$/", $line) > 0){
                    $parts = preg_split("/\\s+/", $line);
                    $httpHeader->set("Method", $parts[0]);
                    $httpHeader->set("Resource", $parts[1]);
                    $httpHeader->set("Version", $parts[2]);
                }else{
                    $httpHeader->set($line, null);
                }
            }
        }
        return $httpHeader;
    }
}
