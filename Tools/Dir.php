<?php
namespace com\github\tncrazvan\CatPaw\Tools;

abstract class Dir{
    /**
     * Get the contents of a directory in one single string recursively.
     * @param string $root the directory to be resolved
     * @param int &$lastModified an pointer to an initialized integer.
     * The method will update this pointer with the unix timestamp of the last change
     * in the given directory.
     * @return string the contents of the directory
     */
    public static function getContentsRecursive(string $root,int &$lastModified):string{
        $fn = end(explode("/",$root));
        if(is_dir($root)){
            $scan = scandir($root);
            $result = array();
            foreach ($scan as $a => &$file){
                if($file == "." || $file == ".." || $file == ".git") continue;
                $result[$file]=self::getContentsRecursive("$root/$file",$lastModified);
            }
            return $result;
        }else{
            $tmpTime = filemtime($root);
            if($tmpTime > $lastModified) $lastModified = $tmpTime;
            return file_get_contents($root);
        }
    }
}