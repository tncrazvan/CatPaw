<?php
namespace com\github\tncrazvan\CatPaw\Tools;

abstract class Session{
    /**
     * Check for a session directory, if it exists, try to umount 
     * it (to make sure it's not) to make sure it's not mounted as a ramdisk,
     * then remove session directory.
     * @return void
     */
    public static function umountSession():void{
        //check if directory already exists
        //if it does there's a chance it's mounted as a ram disk
        if(file_exists(G::$sessionDir)){
            //try to umount the ramdisk
            echo shell_exec("umount ".G::$sessionDir);
        }
        //remove the session directory
        echo shell_exec("rm ".G::$sessionDir." -fr");
    }
    
    /**
     * Try to umount session.
     * After the directory is removed, a new one will be made and mounted as a ramdisk.
     * This new directory will act as a ram space, which means it's faster, however
     * it's limited to the specified size.
     * @return void
     */
    public static function mountSession():void{
        //try to umount session
        self::umountSession();
        //make the session directory again
        echo shell_exec("mkdir ".G::$sessionDir);
        //mount the directory as a new ramdisk
        echo shell_exec("mount -t tmpfs tmpfs ".G::$sessionDir." -o size=".G::$ramSession["size"]);
        //some feedback
        echo "\nRam disk mounted.\n";
    }
}