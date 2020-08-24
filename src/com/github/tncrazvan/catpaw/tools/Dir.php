<?php
namespace com\github\tncrazvan\catpaw\tools;

use InvalidArgumentException;

abstract class Dir{
    /**
     * Get the filenames within a directory recursively.
     * @param root startup directory.
     * @param map an associative array that will hold your results.
     */
    public static function getFilenamesRecursive(string $root,?array &$map):void{
        //$fn = end(explode("/",$root));
        if(is_dir($root)){
            $scan = scandir($root);
            foreach ($scan as $a => &$file){
                if($file == "." || $file == ".." || $file == ".git") continue;
                self::getFilenamesRecursive("$root/$file",$map);
            }
        }else
            $map[] = [
                "name" => $root,
                "size" => filesize($root),
                "lastChange" => filemtime($root)
            ];
    }

    /**
     * Get the contents of a directory in one single string recursively.
     * @param root the directory to be resolved
     * @param lastModified an pointer to an initialized integer.
     * The method will update this pointer with the unix timestamp of the last change
     * in the given directory.
     * @return string the contents of the directory
     */
    public static function getContentsRecursive(string $root,int &$lastModified):string{
        //$fn = end(explode("/",$root));
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

    /**
     * Remove a directory (recursively?).
     * @param dirPath name of the directory.
     * @param recursively if true will try remove all sub directories aswell.<br />
     * <b>NOTE:</b> will fail if false and subdirectories are present.
     * @throws InvalidArgumentException if the specified directory name is not actually a directory.
     * @return void
     */
    public static function remove(string $dirPath, bool $recursively=false):void {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file) && $recursively) {
                self::remove($file, true);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}