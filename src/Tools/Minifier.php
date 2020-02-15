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

    public function &getMinifiedContents(bool $minify=true):array{
        $minified = [
            "js"=>"",
            "css"=>""
        ];
        $size = count($this->assets);
        for($i=0;$i<$size;$i++){
            $filename = $this->dirname.'/'.$this->assets[$i];
            $endsWithJS = Strings::endsWith($filename,".js");
            $endsWithCSS = Strings::endsWith($filename,".css");
            $mtime = \filemtime($filename);
            if($endsWithJS){
                if($minify)
                    $minified["js"] .= "\n".$this->minifyContents($filename,"js");
                else 
                    $minified["js"] .= "\n".file_get_contents($filename);
            }else if($endsWithCSS){
                if($minify)
                    $minified["css"] .= "\n".$this->minifyContents($filename,"css");
                else 
                    $minified["css"] .= "\n".file_get_contents($filename);
            }
            $this->updates[$filename] = $mtime;
        }
        return $minified;
    }

    public function minify(bool $minify = true):void{
        $this->updateAssets();
        $size = count($this->assets);
        $changes = false;
        if($this->lastCount >= 0 || ($this->lastCount >= 0 && $this->lastCount !== $size)){
            try{
                for($i=0;$i<$size;$i++){
                    $filename = $this->dirname.'/'.$this->assets[$i];
                    $endsWithJS = Strings::endsWith($filename,".js");
                    $endsWithCSS = Strings::endsWith($filename,".css");
                    if(!$endsWithJS && !$endsWithCSS)
                        continue;
                    $mtime = \filemtime($filename);
                    if($endsWithJS){
                        if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                            $changes = true;
                            break;
                        }
                    }else if($endsWithCSS){
                        if(!isset($this->updates[$filename]) || $this->updates[$filename] < $mtime){
                            $changes = true;
                            break;
                        }
                    }
                }
            }catch(\Exception $e){
    
            }
        }
        
        $this->lastCount = $size;

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

        \file_put_contents($minified,$contents);
    }
}