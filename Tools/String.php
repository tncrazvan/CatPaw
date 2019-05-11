<?php
namespace com\github\tncrazvan\CatServer\Tools\String;
abstract class String{
    /**
    * Check if string starts with substring
    * @param string $needle substring to look for
    * @param string $haystack string to look into
    * @return bool true if string starts with $needle, otherwise false
    */
   public static function startsWith(string $needle, string $haystack):bool{
       $length = strlen($needle);
       return (substr($haystack, 0, $length) === $needle);
   }

   /**
    * Check if string ends with substring
    * @param string $needle substring to look for
    * @param string $haystack string to look into
    * @return bool true if string ends with $needle, otherwise false
    */
   public static function endsWith(string $needle, string $haystack):bool{
       $length = strlen($needle);
       if ($length == 0) {
           return true;
       }
       return (substr($haystack, -$length) === $needle);
   }
}
