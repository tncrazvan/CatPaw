<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\ServerFile;
use com\github\tncrazvan\catpaw\http\HttpResponse;

class HttpDefaultEvents{
    public static \Closure $notFound;
    public static \Closure $file;
    public static function init():void{
        

        self::$notFound = function(HttpEvent $e){
            $filename = [$e->listener->so->webRoot,$e->listener->path];
            if(!ServerFile::exists(...$filename)){
                $filename = [ServerFile::dirname(...$filename),"index.html"];
                if(!ServerFile::exists(...$filename)){
                    return new HttpResponse([
                        "Status" => Status::NOT_FOUND
                    ]);
                }
            }else if(ServerFile::isDir(...$filename)){
                $filename[] = "index.html";
                if(!ServerFile::exists(...$filename)){
                    return new HttpResponse([
                        "Status" => Status::NOT_FOUND
                    ]);
                }
            }
            return ServerFile::response($e,...$filename);
        };



        self::$file = function(HttpEvent $e){
            switch($e->getRequestMethod()){
                case "GET":
                    $filename = $e->listener->path === ""?"/index.html":$e->listener->path;
                    return ServerFile::response($e,$e->listener->so->webRoot,$filename);
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