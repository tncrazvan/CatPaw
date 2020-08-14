<?php
namespace com\github\tncrazvan\catpaw\tools\formdata;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpRequestBody;

class FormData {
    public static function parse(HttpEvent $e,string &$input,?array &$entries = []){
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $e->getRequestHeader("Content-Type"), $matches);
        $boundary = $matches[1];
        
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        $input = null;
        $entries = [];
        // loop data blocks
        foreach ($a_blocks as &$block){
            if (empty($block))
                continue;

            $entry = [
                'attributes' => '',
                'content_type' => '',
                'body' => '',
            ];
            if(($pieces = \preg_split('/\r\n\r\n/',$block,2)) && count($pieces) > 1){
                $block = null;
                $header = \preg_replace('/(?<=^)\s*/','',$pieces[0]);
                $body = \preg_replace('/\r?\n(?=$)/','',$pieces[1]);
                $pieces = null;
                $lines = \preg_split('/(\r?\n)+/',$header);
                $header = null;
                
                foreach($lines as &$line){

                    if(\preg_match('/(?<=^Content-Disposition:).+/',$line,$attrs)){
                        $attrs = \preg_split('/;\s+/',$attrs[0]);
                        $attrs_len = \count($attrs);
                        $attributes = [];
                        for($i=0;$i<$attrs_len;$i++){
                            $attrs[$i] = \preg_replace('/^\s+/','',$attrs[$i]);
                            if(\preg_match('/(.+)(\=\")(.+)(\")/',$attrs[$i],$pair)){
                                $attributes[$pair[1]] = $pair[3];
                            }
                        }
                        $entry['attributes'] = &$attributes;
                    }else if(\preg_match('/(?<=^Content-Type:).+/',$line,$ct)){
                        $content_type = \preg_replace('/^\s+/','',$ct[0]);
                        $entry['content_type'] = &$content_type;
                    }

                    
                }
                if(isset($entry['attributes']['name'])){
                    $entry['body'] = $body;
                    $body = null;
                    $entries[$entry['attributes']['name']] = $entry;
                    $entry = null;
                }
            }
        }
    }
}