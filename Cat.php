<?php

namespace com\github\tncrazvan\CatServer;

class Cat{
    public static
            $sleep = 1, //microseconds
            $listen=true,
            $groups_allowed=false,
            $smtp_allowed=false,
            $backlog=50,
            $port=80,
            $timeout=3000,
            $web_root="src/",
            $charset="UTF-8",
            $bind_address="127.0.0.1",
            $http_controller_package_name="com\\github\\tncrazvan\\CatServer\\Controller\\Http",
            $ws_controller_package_name="com\\github\\tncrazvan\\CatServer\\Controller\\WebSocket",
            $http_not_found_name="ControllerNotFound",
            $ws_not_found_name="ControllerNotFound",
            $http_default_name="App",
            $ws_events,
            $cookie_ttl=60*60*24*365, //year
            $ws_group_max_client=10,
            $ws_mtu=65536,
            $http_mtu=65536,
            $cache_max_age=60*60*24*365, //year
            $entry_point="/index.html",
            $ws_accept_key = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11",
            $main_settings,
            $running=true;
    
    const DATE_FORMAT = "D j M Y G:i:s T";
    const PATTERN_JS_ESCAPE_LEFT_START = "<\\s*(?=script)";
    const PATTERN_JS_ESCAPE_LEFT_END = "<\\s*\\/\\s*(?=script)";
    const PATTERN_JS_ESCAPE_RIGHT_START1 = "(?<=\\&lt\\;script)\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_START2 = "(?<=\\&lt\\;script).*\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_END = "(?<=&lt;\\/script)>";
    const 
        //INFORMATINOAL RESPONSES
        STATUS_CONTINUE = "100 Continue",
        STATUS_SWITCHING_PROTOCOLS = "101 Switching Protocols",
        STATUS_PROCESSING = "102 Processing",

        //SUCCESS
        STATUS_SUCCESS = "200 OK",
        STATUS_CREATED = "201 CREATED",
        STATUS_ACCEPTED = "202 ACCEPTED",
        STATUS_NON_AUTHORITATIVE_INFORMATION = "203 Non-Authoritative Information",
        STATUS_NO_CONTENT = "204 No Content",
        STATUS_RESET_CONTENT = "205 Reset Content",
        STATUS_PARTIAL_CONTENT = "206 Partial Content",
        STATUS_MULTI_STATUS = "207 Multi-Status",
        STATUS_ALREADY_REPORTED = "208 Already Reported",
        STATUS_IM_USED = "226 IM Used",

        //REDIRECTIONS
        STATUS_MULTIPLE_CHOICES = "300 Multiple Choices",
        STATUS_MOVED_PERMANENTLY = "301 Moved Permanently",
        STATUS_FOUND = "302 Found",
        STATUS_SEE_OTHER = "303 See Other",
        STATUS_NOT_MODIFIED = "304 Not Modified",
        STATUS_USE_PROXY = "305 Use Proxy",
        STATUS_SWITCH_PROXY = "306 Switch Proxy",
        STATUS_TEMPORARY_REDIRECT = "307 Temporary Redirect",
        STATUS_PERMANENT_REDIRECT = "308 Permanent Redirect",

        //CLIENT ERRORS
        STATUS_BAD_REQUEST = "400 Bad Request",
        STATUS_UNAUTHORIZED = "401 Unauthorized",
        STATUS_PAYMENT_REQUIRED = "402 Payment Required",
        STATUS_FORBIDDEN = "403 Forbidden",
        STATUS_NOT_FOUND = "404 Not Found",
        STATUS_METHOD_NOT_ALLOWED = "405 Method Not Allowed",
        STATUS_NOT_ACCEPTABLE = "406 Not Acceptable",
        STATUS_PROXY_AUTHENTICATION_REQUIRED = "407 Proxy Authentication Required",
        STATUS_REQUEST_TIMEOUT = "408 Request Timeout",
        STATUS_CONFLICT = "409 Conflict",
        STATUS_GONE = "410 Gone",
        STATUS_LENGTH_REQUIRED = "411 Length Required",
        STATUS_PRECONDITION_FAILED = "412 Precondition Failed",
        STATUS_PAYLOAD_TOO_LARGE = "413 Payload Too Large",
        STATUS_URI_TOO_LONG = "414 URI Too Long",
        STATUS_UNSUPPORTED_MEDIA_TYPE = "415 Unsupported Media Type",
        STATUS_RANGE_NOT_SATISFIABLE = "416 Range Not Satisfiable",
        STATUS_EXPECTATION_FAILED = "417 Expectation Failed",
        STATUS_IM_A_TEAPOT = "418 I'm a teapot",
        STATUS_MISDIRECTED_REQUEST = "421 Misdirected Request",
        STATUS_UNPROCESSABLE_ENTITY = "422 Unprocessable Entity",
        STATUS_LOCKED = "423 Locked",
        STATUS_FAILED_DEPENDENCY = "426 Failed Dependency",
        STATUS_UPGRADE_REQUIRED = "428 Upgrade Required",
        STATUS_PRECONDITION_REQUIRED = "429 Precondition Required",
        STATUS_TOO_MANY_REQUESTS = "429 Too Many Requests",
        STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = "431 Request Header Fields Too Large",
        STATUS_UNAVAILABLE_FOR_LEGAL_REASONS = "451 Unavailable For Legal Reasons",

        //SERVER ERRORS
        STATUS_INTERNAL_SERVER_ERROR = "500 Internal Server Error",
        STATUS_NOT_IMPLEMENTED = "501 Not Implemented",
        STATUS_BAD_GATEWAY = "502 Bad Gateway",
        STATUS_SERVICE_UNAVAILABLE = "503 Service Unavailable",
        STATUS_GATEWAY_TIMEOUT = "504 Gateway Timeout",
        STATUS_HTTP_VERSION_NOT_SUPPORTED = "505 HTTP Version Not Supported",
        STATUS_VARIANT_ALSO_NEGOTATIES = "506 Variant Also Negotiates",
        STATUS_INSUFFICIENT_STORAGE = "507 Insufficient Storage",
        STATUS_LOOP_DETECTED = "508 Loop Detected",
        STATUS_NOT_EXTENDED = "510 Not Extended",
        STATUS_NETWORK_AUTHENTICATION_REQUIRED = "511 Network Authentication Required";
    
    protected static function getClassNameIndex(string $root, array &$location):int{
        $classname = $root;
        $location_length = count($location);
        for($i=0;$i<$location_length;$i++){
            $classname .="\\".$location[$i];
            if(class_exists($classname)){
                return $i;
            }
        }
        throw new \Exception("Class not found");
    }
    
    protected static function resolveClassName(int $class_id, string $root, array &$location):string{
        $classname = $root;
        $location_length = count($location);
        for($i=0;$i<$location_length;$i++){
            $classname .="\\".$location[$i];
        }
        return $classname;
    }
    
    protected static function resolveMethodArgs(int $offset, array &$location):array{
        $args = [];
        $location_length = count($location);
        if($location_length-1>$offset-1){
            $args = array_slice($args, $offset);
        }
        return $args;
    }
    
    public static function escapeJs(string $content):string{
        return 
        preg_replace(self::PATTERN_JS_ESCAPE_LEFT_START, "&lt;", 
            preg_replace(self::PATTERN_JS_ESCAPE_LEFT_END, "&lt;/", 
                preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_END, "&gt;", 
                    preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START1,"&gt;",
                        preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START2,"&gt;",$content)
                    )
                )
            )
        );
    }
    
    public static function getContentType(string $location):string{
        return self::resolveContentType($location);
    }
    
    /**
     * Returns the mime type of the given resource.
     * For example, given the filename "/index.html", the mime type returned will be "text/html".
     * This can be useful when sending data to your clients.
     * @param location resource name.
     * @return the mime type of the given resource as a String.
     */
    public static function resolveContentType(string $location):string{
        $tmp_type = "";
        $tmp_type0 = preg_split("/\\//",$location);
        $tmp_type0_length = count($tmp_type0);
        if($tmp_type0_length > 0){
            $tmp_type1 = preg_split("/\\./",$tmp_type0[$tmp_type0_length-1]);
            $tmp_type1_length = count($tmp_type1);
            if($tmp_type1_length > 1){
                $tmp_type = $tmp_type1[$tmp_type1_length-1];
            }else{
                $tmp_type = "";
            }
        }else{
            $tmp_type = "";
        }
        
        switch($tmp_type){
            case "html":return "text/html";
            case "css": return "text/css";
            case "csv": return "text/csv";
            case "ics": return "text/calendar";
            case "txt": return "text/plain";

            case "ttf": return "font/ttf";
            case "woff": return "font/woff";
            case "woff2": return "font/woff2";

            case "aac":return "audio/aac";
            case "mid": 
            case "midi":return "audio/midi";
            case "oga":return "audio/og";
            case "wav":return "audio/x-wav";
            case "weba":return "audio/webm";
            case "mp3":return "audio/mpeg";

            case "ico":return "image/x-icon";
            case "jpeg": 
            case "jpg":return "image/jpeg";
            case "png":return "image/png";
            case "gif":return "image/gif";
            case "bmp":return "image/bmp";
            case "svg":return "image/svg+xml";
            case "tif": 
            case "tiff":return "image/tiff";
            case "webp":return "image/webp";

            case "avi":return "video/x-msvideo";
            case "mp4":return "video/mp4";
            case "mpeg":return "video/mpeg";
            case "ogv":return "video/ogg";
            case "webm":return "video/webm";
            case "3gp":return "video/3gpp";
            case "3g2":return "video/3gpp2";
            case "jpgv":return "video/jpg";

            case "abw":return "application/x-abiword";
            case "arc":return "application/octet-stream";
            case "azw":return "application/vnd.amazon.ebook";
            case "bin":return "application/octet-stream";
            case "bz":return "application/x-bzip";
            case "bz2":return "application/x-bzip2";
            case "csh":return "application/x-csh";
            case "doc":return "application/msword";
            case "epub":return "application/epub+zip";
            case "jar":return "application/java-archive";
            case "js":return "application/javascript";
            case "json":return "application/json";
            case "mpkg":return "application/vnd.apple.installer+xml";
            case "odp":return "application/vnd.oasis.opendocument.presentation";
            case "ods":return "application/vnd.oasis.opendocument.spreadsheet";
            case "odt":return "application/vnd.oasis.opendocument.text";
            case "ogx":return "application/ogg";
            case "pdf":return "application/pdf";
            case "ppt":return "application/vnd.ms-powerpoint";
            case "rar":return "application/x-rar-compressed";
            case "rtf":return "application/rtf";
            case "sh":return "application/x-sh";
            case "swf":return "application/x-shockwave-flash";
            case "tar":return "application/x-tar";
            case "vsd":return "application/vnd.visio";
            case "xhtml":return "application/xhtml+xml";
            case "xls":return "application/vnd.ms-excel";
            case "xml":return "application/xml";
            case "xul":return "application/vnd.mozilla.xul+xml";
            case "zip":return "application/zip";
            case "7z":return "application/x-7z-compressed";
            case "apk":return "application/vnd.android.package-archive";
            
            default: return "";
        }
        
    }
}