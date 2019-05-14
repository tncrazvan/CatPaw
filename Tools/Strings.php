<?php
namespace com\github\tncrazvan\CatPaw\Tools;

abstract class Strings{
    const DATE_FORMAT = "D j M Y G:i:s T";
    const PATTERN_DOUBLE_SLASH = "/\\/\\//";
    const PATTERN_JS_ESCAPE_LEFT_START = "<\\s*(?=script)";
    const PATTERN_JS_ESCAPE_LEFT_END = "<\\s*\\/\\s*(?=script)";
    const PATTERN_JS_ESCAPE_RIGHT_START1 = "(?<=\\&lt\\;script)\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_START2 = "(?<=\\&lt\\;script).*\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_END = "(?<=&lt;\\/script)>";


    public static function escapeJs(string $content):string{
        return 
        preg_replace(self::PATTERN_JS_ESCAPE_LEFT_START, "&lt;", 
            preg_replace(self::PATTERN_JS_ESCAPE_LEFT_END, "&lt;/", 
                preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_END, "&gt;", 
                    preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START1,"&gt;",
                        preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START2,"&gt;",$content)
                    )
                )
            )
        );
    }
    
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
