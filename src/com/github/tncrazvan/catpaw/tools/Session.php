<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\tools\SharedObject;

abstract class Session{
    /**
     * Check for a session directory, if it exists, try to umount 
     * it (to make sure it's not) to make sure it's not mounted as a ramdisk,
     * then remove session directory.
     * @param so an SharedObject instance containing the required information to umount the session.
     * @return void
     */
    public static function umount(SharedObject $so):void{
        //check if directory already exists
        //if it does there's a chance it's mounted as a ram disk
        //if(file_exists($so->getSessionDirectory())){
        if(is_dir($so->getSessionDirectory())){
            //try to umount the ramdisk
            echo exec("umount ".$so->getSessionDirectory());
            //remove the session directory recursively
            Dir::remove($so->getSessionDirectory(),true);
        }
    }
    
    /**
     * Try to umount session.
     * After the directory is removed, a new one will be made and mounted as a ramdisk.
     * This new directory will act as a ram space, which means it's faster, however
     * it's limited to the specified size.
     * @param so an SharedObject instance containing the required information to mount the session.
     * @return void
     */
    public static function mount(SharedObject $so):void{
        //try to umount session
        self::umount($so);
        try{
            //make the session directory again
            mkdir($so->getSessionDirectory());
            //mount the directory as a new ramdisk
            echo exec("mount -t tmpfs tmpfs ".$so->getSessionDirectory()." -o size=".$so->getRamSession()["size"]);
            //some feedback
            echo "\nRam disk mounted.\n";
        }catch(\Exception $e){
            mkdir($so->getSessionDirectory());
        }
    }

    /**
     * Try to umount session.
     * After the session is removed, a new one will be created.
     * @param so an SharedObject instance containing the required information to make the session directory.
     * @return void
     */
    public static function init(SharedObject $so):void{
        //try to umount session
        self::umount($so);
        mkdir($so->getSessionDirectory());
    }
}