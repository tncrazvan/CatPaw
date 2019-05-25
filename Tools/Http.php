<?php
namespace com\github\tncrazvan\CatPaw\Tools;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Mime;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;

abstract class Http{
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


    /**
     * Get the contents of a file.
     * @param HttpHeader $clientHeader An HttpHeader that indicates the header of the request.
     * This is needed so that the method can process other metadata such as byte range fields.
     * @param array $filename An array of strings containing the name of the file. 
     * The elements of this array will be joined on "/" and create a filename.
     * @return HttpResponse This method manages byterange requests.
     * If the request header contains byterange fields, Content-Type will be set as 
     * "multipart/byteranges; boundary=$boundary" and the data will be sent as a byterange response, otherwise the Content-Type
     * will be determined using the Mime::getContentType method.
     * 
     * In both cases, regardless if the request is a byterange request or not, the method will resolve the data as a byterange response.
     * The response ranges will be set as specified by the request header fields 
     * or from 0 to the end of file if ranges are not found.
     */
    public static function getFile(HttpHeader &$clientHeader, string ...$filename):HttpResponse{
        $resultHeader = new HttpHeader();
        $result = "";
        $filenameLength = count($filename);
        if($filenameLength === 0) return $result;
        $filename = preg_replace('#/+#','/',filter_var(join("/",$filename), FILTER_SANITIZE_URL));
        $filesize = filesize($filename);
        $raf = fopen($filename,"r");
        $fileLength = filesize($filename);

        $lastModified=filemtime($filename);
        $resultHeader->set("Last-Modified", date(Strings::DATE_FORMAT, $lastModified));
        $resultHeader->set("Last-Timestamp", $lastModified);
        
        $ctype = Mime::getContentType($filename);
        
        if($clientHeader->has("Range")){
            $resultHeader->setStatus(G::STATUS_PARTIAL_CONTENT);
            $ranges = preg_split("/,/",preg_split("/=/",$clientHeader->get("Range"))[1]);
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
                $boundary = G::generateMultipartBoundary();
                $resultHeader->setContentType("multipart/byteranges; boundary=$boundary");
                
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

                    $result .= $startConnectionStr;
                
                    if($end-$start+1 > G::$httpMtu){
                        $remainingBytes = $end-$start+1;
                        $readLength = G::$httpMtu;
                        fseek($raf, $start);
                        while($remainingBytes > 0){
                            $result = fread($raf, $readLength);
                            $remainingBytes -= G::$httpMtu;
                            if($remainingBytes < 0){
                                $readLength = $remainingBytes+G::$httpMtu;
                                $remainingBytes = 0;
                            }
                        }
                    }else{
                        fseek($raf, $start);
                        $result = fread($raf, $end-$start+1);
                    }
                    if($i > $rangeStartLength-1){
                        $result .= "\r\n";
                    }
                }
                $endConnectionStr = "\r\n--$boundary--";
                $result .= $endConnectionStr;
            }else{
                $start = $rangeStart[0];
                $end = $rangeEnd[0];
                if($filesize-1 > $start){
                    if($filesize-1 < $end){
                        $end = $filesize-1;
                    }
                    $len = $end-$start+1;
                    $resultHeader->set("Content-Range", "bytes $start-$end/$fileLength");
                    $resultHeader->set("Content-Length", $len);
                    fseek($raf, $start);
                    $result = fread($raf,$end-$start+1);
                }
            }
        }else{
            $resultHeader->set("Content-Type",$ctype);
            $resultHeader->set("Content-Length", $fileLength);
            if($filesize > 0){
                fseek($raf, 0);
                $result = fread($raf, $fileLength);
            }
        }
        fclose($raf);
        return new HttpResponse($resultHeader,$result);
    }
}