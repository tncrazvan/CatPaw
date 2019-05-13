<?php
namespace com\github\tncrazvan\CatServer\Tools;

abstract class Dir{
    public static function getContents($root,&$lastModified){
        $fn = end(explode("/",$root));
        if(is_dir($root)){
            $scan = scandir($root);
            $result = array();
            foreach ($scan as $a => &$file){
                if($file == "." || $file == ".." || $file == ".git") continue;
                $result[$file]=getData("$root/$file",$lastModified);
            }
            return $result;
        }else{
            $tmpTime = filemtime($root);
            if($tmpTime > $lastModified) $lastModified = $tmpTime;
            if(startsWith($fn,"tooltip")) {
                //tooltips must be read as html not json, so get the raw content
                return file_get_contents($root);
            }else{
                return json_decode(file_get_contents($root));
            }
        }
    }
}