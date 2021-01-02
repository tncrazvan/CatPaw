<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\ServerFile;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\Strings;

class HttpDefaultEvents{
    public static \Closure $notFound;
    public static \Closure $file;
    public static function init():void{
        $webroot = '';
        static::$notFound = function(HttpEvent $e) use(&$webroot){
            if('' === $webroot)
                $webroot = $e->getHttpEventListener()->getSharedObject()->getWebRoot();
                
            $filename = [$webroot,$e->getHttpEventListener()->getPath()];
            if(!ServerFile::exists(...$filename)){
                if(!ServerFile::exists($webroot,"index.html")){
                    return new HttpResponse([
                        "Status" => Status::NOT_FOUND
                    ]);
                }else $filename = [$webroot,"index.html"];
            }else if(ServerFile::isDir(...$filename)){
                $html = [...$filename,"index.html"];
                if(!ServerFile::exists(...$html)){
                    return new HttpResponse([
                        "Status" => Status::NOT_FOUND
                    ]);
                }else $filename = $html;
            }
            return ServerFile::response($e,...$filename);
        };



        static::$file = function(HttpEvent $e) use(&$webroot){
            if('' === $webroot)
                $webroot = $e->getHttpEventListener()->getSharedObject()->getWebRoot();
                
            switch($e->getRequestMethod()){
                case "GET":
                    return ServerFile::response($e,$webroot,$e->getHttpEventListener()->getPath());
                break;
                default:
                    return new HttpResponse([
                        "Status"=>Status::METHOD_NOT_ALLOWED
                    ]);
                break;
            }
        };

        


    }
}