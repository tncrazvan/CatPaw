<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\tools\SharedObject;

abstract class Session{
    /**
     * Check for a session directory, if it exists, try to umount 
     * it (to make sure it's not) to make sure it's not mounted as a ramdisk,
     * then remove session directory.
     * @return void
     */
    public static function umount(SharedObject $so):void{
        //check if directory already exists
        //if it does there's a chance it's mounted as a ram disk
        if(file_exists($so->sessionDir)){
            //try to umount the ramdisk
            echo exec("umount ".$so->sessionDir);
            //remove the session directory recursively
            Dir::remove($so->sessionDir,true);
        }
    }
    
    /**
     * Try to umount session.
     * After the directory is removed, a new one will be made and mounted as a ramdisk.
     * This new directory will act as a ram space, which means it's faster, however
     * it's limited to the specified size.
     * @return void
     */
    public static function mount(SharedObject $so):void{
        //try to umount session
        self::umount($so);
        try{
            //make the session directory again
            mkdir($so->sessionDir);
            //mount the directory as a new ramdisk
            echo exec("mount -t tmpfs tmpfs ".$so->sessionDir." -o size=".$so->ramSession["size"]);
            //some feedback
            echo "\nRam disk mounted.\n";
        }catch(\Exception $e){
            mkdir($so->sessionDir);
        }
    }

    /**
     * Try to umount session.
     * After the session is removed, a new one will be made.
     * @return void
     */
    public static function init(SharedObject $so):void{
        //try to umount session
        self::umount($so);
        mkdir($so->sessionDir);
    }
}