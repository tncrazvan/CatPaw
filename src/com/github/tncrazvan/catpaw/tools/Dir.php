<?php
namespace com\github\tncrazvan\catpaw\tools;

use InvalidArgumentException;

abstract class Dir{
    
    /**
     * Check for a session directory, if it exists, try to umount 
     * it (to make sure it's not) to make sure it's not mounted as a ramdisk,
     * then remove session directory.
     * @param so an SharedObject instance containing the required information to umount the session.
     * @return void
     */
    public static function umount(string $dirname):void{
        //check if directory already exists
        //if it does there's a chance it's mounted as a ram disk
        //if(file_exists($so->getSessionDirectory())){
        if(is_dir($dirname)){
            //try to umount the ramdisk
            echo exec("umount $dirname");
            //remove the session directory recursively
            static::remove($dirname,true);
        }
    }
    
    /**
     * Try to umount session.
     * After the directory is removed, a new one will be made and mounted as a ramdisk.
     * This new directory will act as a ram space, which means it's faster, however
     * it's limited to the specified size.
     * @param so an SharedObject instance containing the required information to mount the session.
     * @param size Ram session size in MBs.
     * @return void
     */
    public static function mount(string $dirname, int $size):void{
        //try to umount session
        static::umount($dirname);
        try{
            //make the session directory again
            mkdir($dirname);
            //mount the directory as a new ramdisk
            echo exec("mount -t tmpfs tmpfs $dirname -o size={$size}MB");
            //some feedback
            echo "\nRam disk mounted.\n";
        }catch(\Exception $e){
            mkdir($dirname);
        }
    }


    /**
     * Alias of findFilesRecursive
     * Get the filenames within a directory recursively.
     * @param root startup directory.
     * @param map an associative array that will hold your results.
     */
    public static function getFilenamesRecursive(string $root,?array &$map):void{
        static::findFilesRecursive($root,$map);
    }

    /**
     * Get the filenames within a directory recursively.
     * @param root startup directory.
     * @param map an associative array that will hold your results.
     */
    public static function findFilesRecursive(string $root,?array &$map):void{
        $root = \preg_replace('/\/++/','/',$root);
        //$fn = end(explode("/",$root));
        if(\is_dir($root)){
            $scan = \scandir($root);
            foreach ($scan as $a => &$file){
                if($file == "." || $file == ".." || $file == ".git") continue;
                self::getFilenamesRecursive("$root/$file",$map);
            }
        }else
            $map[] = [
                "name" => $root,
                "size" => \filesize($root),
                "lastChange" => \filemtime($root)
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