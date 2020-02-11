<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\http\HttpController;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\Mime;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\Strings;

abstract class Http{
    public static function generateMultipartBoundary():string{
        return md5(uniqid(rand(), true));
    }

    /**
     * Get the contents of a file.
     * @param HttpController controller The controller calling this method.
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
    public static function getFile(HttpController $controller, string ...$filename):HttpResponse{
        $requestHeaders = $controller->getRequestHeaders();
        $responseHeaders = new HttpHeaders($controller);
        $result = "";
        $filenameLength = count($filename);
        if($filenameLength === 0) return $result;
        $filename = preg_replace('#/+#','/',join("/",$filename));
        $filesize = filesize($filename);
        $raf = fopen($filename,"r");
        if(!$raf){
            return new HttpResponse(null,[
                "Status"=>Status::NOT_FOUND
            ]);
        }
        $fileLength = filesize($filename);

        $lastModified=filemtime($filename);
        $responseHeaders->set("Last-Modified", date(Strings::DATE_FORMAT, $lastModified));
        $responseHeaders->set("Last-Timestamp", $lastModified);
        
        $ctype = Mime::getContentType($filename);
        
        if($requestHeaders->has("Range")){
            $ranges = preg_split("/,/",preg_split("/=/",$requestHeaders->get("Range"))[1]);
            $rangesLength = count($ranges);
            $rangeStart = array_fill(0, $rangesLength, null);
            $rangeEnd = array_fill(0, $rangesLength, null);
            $lastIndex = null;
            for($i = 0; $i < $rangesLength; $i++){
                $lastIndex = strlen($ranges[$i])-1;
                $tmp = preg_split("/-/",$ranges[$i]);
                if(substr($ranges[$i], 0, 1) === "-"){
                    $rangeStart[$i] = 0;
                }else{
                    $rangeStart[$i] = intval($tmp[0]);
                }
                if(substr($ranges[$i], $lastIndex,$lastIndex+1) === "-"){
                    $rangeEnd[$i] = $fileLength-1;
                }else{
                    $rangeEnd[$i] = intval($tmp[1]);
                }
            }
            $start = null;
            $end = null;
            $rangeStartLength = count($rangeStart);
            if($rangeStartLength > 1){
                $responseHeaders->setStatus(Status::PARTIAL_CONTENT);
                $boundary = self::generateMultipartBoundary();
                $responseHeaders->setContentType("multipart/byteranges; boundary=$boundary");
                
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
                
                    if($end-$start+1 > SharedObject::$httpMtu){
                        $remainingBytes = $end-$start+1;
                        $readLength = SharedObject::$httpMtu;
                        fseek($raf, $start);
                        while($remainingBytes > 0){
                            $result = fread($raf, $readLength);
                            $remainingBytes -= SharedObject::$httpMtu;
                            if($remainingBytes < 0){
                                $readLength = $remainingBytes+SharedObject::$httpMtu;
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
                    $responseHeaders->set("Content-Range", "bytes $start-$end/$fileLength");
                    $responseHeaders->set("Content-Length", $len);
                    fseek($raf, $start);
                    $result = fread($raf,$end-$start+1);
                }
            }
        }else{
            $responseHeaders->set("Content-Type",$ctype);
            $responseHeaders->set("Content-Length", $fileLength);
            if($filesize > 0){
                fseek($raf, 0);
                $result = fread($raf, $fileLength);
            }
        }
        fclose($raf);
        return new HttpResponse($responseHeaders,$result);
    }
}