<?php
namespace com\github\tncrazvan\catpaw\tools\helpers\parsing;

use com\github\tncrazvan\catpaw\tools\Caster;
use com\github\tncrazvan\catpaw\tools\formdata\FormData;

class BodyParser{
    public static function &parse(string &$body, string &$ctype, ?string $classname=null,bool $toarray = false){
        if('' === $ctype){
            $result = null;
            throw new \Exception("No Content-Type specified. Could not parse body.");
            return $result;
        }else if($classname !== null){
            if(str_starts_with($ctype,"application/x-www-form-urlencoded")){
                \mb_parse_str($body,$result);
            }else if(str_starts_with($ctype,"application/json")){
                $result = \json_decode($body);
            }else if(str_starts_with($ctype,"multipart/")){
                $result = null;
                FormData::parse($ctype,$body,$result);
            }else{
                echo "No matching Content-Type ($ctype), falling back to null.\n";
                $result = null;
                return $result;
            }
            $result = &Caster::cast($result,$classname);
            return $result;
        }else if($toarray) try {
            if(str_starts_with($ctype,"application/x-www-form-urlencoded")){
                \mb_parse_str($body,$result);
                return $result;
            }else if(str_starts_with($ctype,"application/json")){
                $result = \json_decode($body,true);
                return $result;
            }else if(str_starts_with($ctype,"multipart/")){
                FormData::parse($ctype,$body,$result);
                return $result;
            }else{
                echo "No matching Content-Type ($ctype), falling back to empty array.\n";
                $result = [];
                return $result;
            }
        }catch(\Exception $e){
            echo "Could not convert body to array, falling back to empty array.\n";
            $result = [];
            return $result;
        }
    }
}