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

        static::$notFound = function(HttpEvent $e){
            $filename = [$e->getHttpEventListener()->getSharedObject()->getWebRoot(),$e->getHttpEventListener()->getPath()];
            if(!ServerFile::exists(...$filename)){
                $php = [ServerFile::dirname(...$filename),"index.php"];
                if(!ServerFile::exists(...$php)){
                    $html = [ServerFile::dirname(...$filename),"index.html"];
                    if(!ServerFile::exists(...$html)){
                        return new HttpResponse([
                            "Status" => Status::NOT_FOUND
                        ]);
                    }else $filename = $html;
                }else $filename = $php;
            }else if(ServerFile::isDir(...$filename)){
                $php = [...$filename,"index.php"];
                if(!ServerFile::exists(...$php)){
                    $html = [...$filename,"index.html"];
                    if(!ServerFile::exists(...$html)){
                        return new HttpResponse([
                            "Status" => Status::NOT_FOUND
                        ]);
                    }else $filename = $html;
                }else $filename = $php;
            }
            if(Strings::endsWith($filename[count($filename)-1],'.php'))
                return ServerFile::include(join('/',$filename));
            return ServerFile::response($e,...$filename);
        };



        static::$file = function(HttpEvent $e){
            
            switch($e->getRequestMethod()){
                case "GET":
                    if(Strings::endsWith($e->getHttpEventListener()->getPath(),'.php'))
                        return ServerFile::include(join('/',[$e->getHttpEventListener()->getSharedObject()->getWebRoot(),$e->getHttpEventListener()->getPath()]));
                    return ServerFile::response($e,$e->getHttpEventListener()->getSharedObject()->getWebRoot(),$e->getHttpEventListener()->getPath());
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