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
        $script,
        $lastCount=-1;

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

    public function minify(bool $minify = true):void{
        $this->updateAssets();
        $size = count($this->assets);
        $changes = false;
        if($this->lastCount >= 0 || ($this->lastCount >= 0 && $this->lastCount !== $size)){
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
                            if($minify)
                                $this->js .= "\n".$this->minifyContents($filename,"js");
                            else 
                                $this->js .= "\n".file_get_contents($filename);
                        }
                    }else if(Strings::endsWith($filename,".css")){
                        if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                            $changes = true;
                            $this->updates[$filename] = $mtime;
                            if($minify)
                                $this->css .= "\n".$this->minifyContents($filename,"css");
                            else 
                                $this->css .= "\n".file_get_contents($filename);
                        }
                    }
                }
            }catch(\Exception $e){
    
            }
        }
        
        $this->lastCount = $size;

        if(!$changes) return;

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

        \file_put_contents($minified,$contents);
    }
}