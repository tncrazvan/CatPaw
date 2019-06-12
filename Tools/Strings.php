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

    /**
     * @param string $data The data to encode.
     * @param int $level The level of compression. Can be given as 0 for no compression 
     * up to 9 for maximum compression. If not given, the default compression level will 
     * be the default compression level of the zlib library.
     * @param int $encodingMode The encoding mode. Can be FORCE_GZIP (the default) or FORCE_DEFLATE.
     * Prior to PHP 5.4.0, using FORCE_DEFLATE results in a standard zlib deflated string 
     * (inclusive zlib headers) after a gzip file header but without the trailing crc32 checksum.
     * In PHP 5.4.0 and later, FORCE_DEFLATE generates RFC 1950 compliant output, consisting of 
     * a zlib header, the deflated data, and an Adler checksum.
     * @return string The encoded string, or FALSE if an error occurred.
     */
    public static function compress(&$type,string &$data,array &$order=["deflate","gzip"], array &$accepted=null):bool{
        $max = count($order);
        if($accepted === null){
            $type = "deflate";
            $data = gzdeflate($data);
            return false;
        }else{
            $len = count($order);
            for($i=0;$i<$len;$i++){
                if(in_array($order[$i],$accepted)){
                    $type = $order[$i];
                    switch($order[$i]){
                        case "deflate":
                            $data = gzdeflate($data);
                            break;
                        case "gzip":
                            $data = gzcompress($data);
                            break;
                        default:
                         return false;
                    }
                    return true;
                }
            }
            return false;
        }
    }

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
