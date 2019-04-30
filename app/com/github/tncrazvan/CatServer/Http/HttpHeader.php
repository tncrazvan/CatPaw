<?php
namespace com\github\tncrazvan\CatServer\Http;

class HttpHeader{
    private $headers = [],$cookies = [];
    const DATE_FORMAT = "D j M Y G:i:s T";
    const REGEX_RSEOURCE = "/(?<=\s)\/.*(?=\s\w+)/m";
    const REGEX_STATUS = "/(?<=\s)[0-9]+(?=\s)/m";
    public function __construct(bool $create_success_header = true) {
        if($create_success_header){
            $this->headers["Status"] = "HTTP/1.1 200 OK";
            $this->headers["Date"] = date(self::DATE_FORMAT); 
        }
    }
    
    public function field_to_string(string $key):string{
        if($key === "Resource" || $key === "Status"){
            return $this->headers[$key]."\r\n";
        }
        return $key.": ".$this->headers[$key]."\r\n";
    }
    
    public function cookie_to_string(string $key):string{
        $cookie = $this->cookies[$key];
        return $cookie[4].": "
                .$key."=".$cookie[0]
                .($cookie[1]===null?"":"; path=".$cookie[1])
                .($cookie[2]===null?"":"; domain=".$cookie[2])
                .($cookie[3]===null?"":"; expires=".date(self::DATE_FORMAT,$cookie[3]));
    }
    
    public function to_string():string{
        $result = "";
        foreach(array_keys($this->headers) as $key){
            $result .= $this->field_to_string($key);
        }
        foreach(array_keys($this->cookies) as $key){
            $result .= $this->field_to_string($key);
        }
        return $result;
    }
    
    public function is_defined(string $key):bool{
        return isset($this->headers[$key]);
    }
    
    public function set(string $key, string $content):void{
        $this->headers[$key] = $content;
    }
    
    public function get(string $key){
        if(!isset($this->headers[$key])) return null;
        switch($key){
            case "Status":
            case "Resource":
                return trim($this->headers[$key]);
                break;
            case "Method":
                return trim(explode(" ", $this->headers[$key])[0]);
                break;
            default:
                return trim($this->headers[$key]);
                break;
        }
    }
    
    public function isset_cookie(string $key):bool{
        foreach(array_keys($this->headers) as $needle){
            if(trim(key) === trim($needle)){
                return true;
            }
        }
        return false;
    }
    
    public function get_cookie(string $key):string{
        if(!$this->isset_cookie($key)) return null;
        return urldecode($this->cookies[$key][0]);
    }
    
    public function set_cookie(string $key, string $content, string $path="/", string $domain=null, string $expire=null):void{
        $cookie = array_fill(0, 4, null);
        $cookie[0] = urlencode($content);
        $cookie[1] = $path;
        $cookie[2] = $domain;
        $cookie[3] = $expire;
        $cookie[4] = "Set-Cookie";
        $this->cookies[trim($key)] = $cookie;
    }
    
    public static function from_string(string &$string):HttpHeader{
        $http_header = new HttpHeader();
        $headers = preg_split("/\\r\\n/", $string);
        foreach($headers as $header){
            if($header === "") continue;
            $item = preg_split("/:(?=\\s)/", $header);
            $item_length = count($item);
            if($item_length > 1){
                if($item[0] === "Cookie"){
                    $cookies= preg_split("/;/", $item[1]);
                    foreach($cookies as $cookie){
                        $cookie = preg_split("=(?!\\s|\\s|$)",$cookie);
                        $cookie_length = count($cookie);
                        if($cookie_length > 1){
                            $content = array_fill(0, 4, null);
                            $content[0] = $cookie[1];
                            $content[1] = $cookie_length>2?$cookie[2]:null;
                            $content[2] = $cookie_length>3?$cookie[3]:null;
                            $content[3] = $cookie_length>3?$cookie[3]:null;
                            $content[4] = "Cookie";
                            $this->cookies[trim($key)] = $content;
                        }
                    }
                }else{
                    $http_header->set($item[0], $item[1]);
                }
            }else{
                if(substr($header, 0, 3) === "GET"){
                    $matches = [];
                    preg_match(self::REGEX_RSEOURCE, $header, $matches);
                    $http_header->set("Resource", $matches[0]);
                    $http_header->set("Method", "GET");
                }else if(substr($header, 0, 4) === "POST"){
                    $matches = [];
                    preg_match(self::REGEX_RSEOURCE, $header, $matches);
                    $http_header->set("Resource", $matches[0]);
                    $http_header->set("Method", "POST");
                }
            }
        }
        return $http_header;
    }
}
