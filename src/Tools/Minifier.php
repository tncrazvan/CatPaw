<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Strings;

class Minifier{
    private 
        $inputDirname = "",
        $updates = array(),
        $assets = array(),
        $js = "",
        $css = "";

    private const 
        OUTPUT_DIRNAME = "minified",
        OUTPUT_FILENAME = "minified";
    public function __construct(string $inputDirname,array $assets){
        $this->inputDirname = $inputDirname;
        $this->assets = $assets;
    }

    public static function minifyContents(string $contents,string $type,string $hashCode=""):string{
        $tmp = dirname(Server::$webRoot)."/../tmp";
        if(!\file_exists($tmp)){
            mkdir($tmp);
        }else if(!\is_dir($tmp)){
            unlink($tmp);
            mkdir($tmp);
        }

        $tmp = "$tmp/$hashCode.minified.input.tmp";

        if(\file_exists($tmp))
            unlink($tmp);

        \file_put_contents($tmp,$contents);

        $result = shell_exec(Server::$minifier["location"]." --type=$type \"$tmp\"");
        if($result === null) $result = "";
        if(\file_exists($tmp))
            unlink($tmp);
        return $result;
    }

    public function minify(bool $minify = true):void{
        $size = count($this->assets);
        $changes = false;
        try{
            for($i=0;$i<$size;$i++){
                $filename = $this->inputDirname.$this->assets[$i];
                if(!Strings::endsWith($filename,".js") && !Strings::endsWith($filename,".css"))
                    continue;
                $mtime = \filemtime($filename);
                if(Strings::endsWith($filename,".js")){
                    if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                        $changes = true;
                        $this->updates[$filename] = $mtime;
                        $contents = \file_get_contents($filename);
                        $this->js .= $minify?self::minifyContents($contents,"js"):$contents;
                    }
                }else if(Strings::endsWith($filename,".css")){
                    if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                        $changes = true;
                        $this->updates[$filename] = $mtime;
                        $contents = \file_get_contents($filename);
                        $this->css .= $minify?self::minifyContents($contents,"css"):$contents;
                    }
                }
            }
        }catch(Exception $e){

        }

        if(!$changes) return;

        $this->save($this->js,"js");
        $this->save($this->css,"css");
    }

    public function save(string $contents,string $type):void{
        $dir = $this->inputDirname.self::OUTPUT_DIRNAME;
        if(!\file_exists($dir)){
            mkdir($dir);
        }

        $minified = $dir.'/'.self::OUTPUT_FILENAME.".$type";
        if(\file_exists($minified))
            \unlink($minified);

        \file_put_contents($minified,$contents);
    }
}