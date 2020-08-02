<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\tools\SharedObject;

class Minifier{
    private 
        $dirname = "",
        $updates = array(),
        $assets = array(),
        $assetsFilename,
        $js = "",
        $css = "",
        $server,
        $script;

    private const 
        OUTPUT_DIRNAME = "minified",
        OUTPUT_FILENAME = "minified";
    public function __construct(CatPaw $server, string $script,string $assets){
        $this->server = $server;
        $this->script = $script;
        $this->dirname = dirname($assets);
        $this->assetsFilename = $assets;
    }

    public function updateAssets():void{
        $this->assets = json_decode(file_get_contents($this->assetsFilename),true);
    }

    public function &minifyContents(string $filename,string $type):string{
        $tmpScript = preg_replace('/\@type/',$type,$this->script);
        $tmpScript = preg_replace('/\@filename/',$filename,$tmpScript);
        $result = shell_exec($tmpScript);
        if($result === null) 
            $result = "";
        return $result;
    }

    public function &getMinifiedContents(bool $minify=true):array{
        $result = [
            "js"=>"",
            "css"=>""
        ];
        $size = count($this->assets);
        for($i=0;$i<$size;$i++){
            $filename = $this->dirname.'/'.$this->assets[$i];
            if(Strings::endsWith($filename,".js")){
                if($minify)
                    $result["js"] .= $this->minifyContents($filename,"js")."\n/*=====@filename: $filename====*/\n";
                else 
                    $result["js"] .= file_get_contents($filename)."\n/*=====@filename: $filename====*/\n";
            }else if(Strings::endsWith($filename,".css")){
                if($minify)
                    $result["css"] .= $this->minifyContents($filename,"css")."\n/*=====@filename: $filename====*/\n";
                else 
                    $result["css"] .= file_get_contents($filename)."\n/*=====@filename: $filename====*/\n";
            }
        }
        return $result;
    }

    public function minify(bool $minify = true):void{
        $this->updateAssets();
        $size = count($this->assets);
        $changes = false;
        try{
            for($i=0;$i<$size;$i++){
                $filename = $this->dirname.'/'.$this->assets[$i];
                if(!Strings::endsWith($filename,".js") && !Strings::endsWith($filename,".css"))
                    continue;
                $mtime = \filemtime($filename);
                if(Strings::endsWith($filename,".js")){
                    if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                        $changes = true;
                        $this->updates[$filename] = $mtime;
                    }
                }else if(Strings::endsWith($filename,".css")){
                    if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                        $changes = true;
                        $this->updates[$filename] = $mtime;
                    }
                }
            }
        }catch(\Exception $e){

        }

        if(!$changes) return;

        $minified = &$this->getMinifiedContents($minify);

        $this->js = $minified["js"];
        $this->css = $minified["css"];


        $this->save($this->js,"js");
        $this->save($this->css,"css");
    }

    public function save(string &$contents,string $type):void{
        $dir = $this->dirname.'/'.self::OUTPUT_DIRNAME;
        if(!\file_exists($dir)){
            mkdir($dir);
        }

        $minified = $dir.'/'.self::OUTPUT_FILENAME.".$type";
        if(\file_exists($minified))
            \unlink($minified);
        $len = strlen($contents);
        \file_put_contents($minified,$contents);
    }
}
