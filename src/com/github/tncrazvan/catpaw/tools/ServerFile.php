<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\http\HttpResponse;

class ServerFile{
    /**
     * Include a php script in between \ob_start() and \ob_get_clean().
     * @param name of the file
     * @param arguments to pass to the script.
     * This data will be available inside the script through the "global $_ARGS" variable.
     * This variable can also be a ccessed through Script::args() which also provides type hinting.
     * @return string result of the script.
     */
    public static function include(string $filename,...$args){
        global $_ARGS;
        global $_EVENT;
        $_ARGS = $args;
        $_EVENT->setResponseContentType("text/html");
        \ob_start();
        $currentDir = \getcwd();
        \chdir(\dirname($filename));
        include($filename);
        \chdir($currentDir);
        $_ARGS = null; //remove data after the script is done with it
        return \ob_get_clean();
    }

    /**
     * Require once a php script in between \ob_start() and \ob_get_clean().
     * @param parts of the filename (will be joined on "/").
     * @param arguments to pass to the script.
     * This data will be available inside the script through the "global $_ARGS" variable.
     * This variable can also be a ccessed through Script::args() which also provides type hinting.
     * @return string result of the script.
     */
    public static function requireOnce(string $filename,...$args){
        global $_ARGS;
        global $_EVENT;
        $_ARGS = $args;
        $_EVENT->setResponseContentType("text/html");
        \ob_start();
        $currentDir = \getcwd();
        \chdir(\dirname($filename));
        require_once($filename);
        \chdir($currentDir);
        $_ARGS = null; //remove data after the script is done with it
        return \ob_get_clean();
    }

    /**
     * Get the name of the parent directory of this file
     * @param parts of the filename (will be joined on "/").
     * The elements of this array will be joined on "/" and create a filename.
     * @return bool true if the file exists, false otherwise.
     */
    public static function dirname(string ...$filename):string{
        return \dirname(\preg_replace('#/+#','/',\join("/",$filename)));
    }
    /**
     * Check if a file exists.
     * @param parts of the filename (will be joined on "/").
     * The elements of this array will be joined on "/" and create a filename.
     * @return bool true if the file exists, false otherwise.
     */
    public static function exists(string ...$filename):bool{
        return \file_exists(\preg_replace('#/+#','/',\join("/",$filename)));
    }
    /**
     * Check if a file is a directory.
     * @param parts of the filename (will be joined on "/").
     * The elements of this array will be joined on "/" and create a filename.
     * @return bool true if the file is a directory, false otherwise.
     */
    public static function isDir(string ...$filename):bool{
        return \is_dir(preg_replace('#/+#','/',\join("/",$filename)));
    }
    /**
     * Get the contents of a file as an HttpResponse.
     * This will deal with range requests.
     * @param HttpEvent the event calling this method.
     * This is needed so that the method can process other metadata such as byte range fields.
     * @param filename parts of the filename (will be joined on "/").
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
    public static function response(HttpEvent $event, string ...$filename):HttpResponse{
        $requestHeaders = $event->getRequestHttpHeaders();
        $responseHeaders = new HttpHeaders($event);
        $result = "";
        $filenameLength = \count($filename);
        if($filenameLength === 0) return $result;
        $filename = \preg_replace('#/+#','/',\join("/",$filename));
        //if(!\file_exists($filename))
        if(!\is_file($filename))
            return new HttpResponse([
                "Status"=>Status::NOT_FOUND
            ]);
        $raf = \fopen($filename,"r");
        if(!$raf){
            return new HttpResponse([
                "Status"=>Status::NOT_FOUND
            ]);
        }
        $filesize = \filesize($filename);
        //$fileLength = filesize($filename);

        $lastModified = \filemtime($filename);
        $responseHeaders->set("Last-Modified", \date(Strings::DATE_FORMAT, $lastModified));
        $responseHeaders->set("Last-Timestamp", $lastModified);
        
        $ctype = Mime::getContentType($filename);
        
        if($requestHeaders->has("Range")){
            $responseHeaders->set("Accept-Ranges","bytes");
            $ranges = \preg_split("/,/",\preg_split("/=/",$requestHeaders->get("Range"))[1]);
            $rangesLength = count($ranges);
            $rangeStart = \array_fill(0, $rangesLength, null);
            $rangeEnd = \array_fill(0, $rangesLength, null);
            $lastIndex = null;
            for($i = 0; $i < $rangesLength; $i++){
                $lastIndex = \strlen($ranges[$i])-1;
                $tmp = \preg_split("/-/",$ranges[$i]);
                if(\substr($ranges[$i], 0, 1) === "-"){
                    $rangeStart[$i] = 0;
                }else{
                    $rangeStart[$i] = \intval($tmp[0]);
                }
                if(\substr($ranges[$i], $lastIndex,$lastIndex+1) === "-"){
                    $rangeEnd[$i] = $filesize-1;
                }else{
                    $rangeEnd[$i] = \intval($tmp[1]);
                }
            }
            $start = null;
            $end = null;
            $rangeStartLength = \count($rangeStart);
            if($rangeStartLength > 1){
                $responseHeaders->setStatus(Status::PARTIAL_CONTENT);
                $boundary = Http::generateMultipartBoundary();
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
                    $startConnectionStr .= "Content-Range: bytes $start-$end/$filesize\r\n\r\n";

                    /* if($start === 0 && $end === null) 
                        return new HttpResponse($responseHeaders,$result); */

                    $result .= $startConnectionStr;
                
                    if($end-$start+1 > $event->getHttpEventListener()->getSharedObject()->getHttpMtu()){
                        $remainingBytes = $end-$start+1;
                        $readLength = $event->getHttpEventListener()->getSharedObject()->getHttpMtu();
                        \fseek($raf, $start);
                        while($remainingBytes > 0){
                            $result = \fread($raf, $readLength);
                            $remainingBytes -= $event->getHttpEventListener()->getSharedObject()->getHttpMtu();
                            if($remainingBytes < 0){
                                $readLength = $remainingBytes+$event->getHttpEventListener()->getSharedObject()->getHttpMtu();
                                $remainingBytes = 0;
                            }
                        }
                    }else{
                        \fseek($raf, $start);
                        $result = \fread($raf, $end-$start+1);
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
                    $responseHeaders->setStatus(Status::PARTIAL_CONTENT);
                    $responseHeaders->set("Content-Type",$ctype);
                    $responseHeaders->set("Content-Range", "bytes $start-$end/$filesize");
                    $responseHeaders->set("Content-Length", $len);
                    /* if($start === 0 && $end === null) 
                        return new HttpResponse($responseHeaders,$result); */
                    \fseek($raf, $start);
                    $result = \fread($raf,$end-$start+1);
                }
            }
        }else{
            $responseHeaders->set("Content-Type",$ctype);
            $responseHeaders->set("Content-Length", $filesize);
            if(Strings::startsWith($ctype,'audio/') || Strings::startsWith($ctype,'video/')){
                $responseHeaders->set("Accept-Ranges","bytes");
                \fseek($raf, 0);
                $length = \round($filesize/10)+1;
                if($length> $filesize)
                    $length = $filesize;
                $result = \fread($raf, $length);
            }else if($filesize > 0){
                \fseek($raf, 0);
                $result = \fread($raf, $filesize);
            }
        }
        \fclose($raf);
        return new HttpResponse($responseHeaders,$result);
    }
}
