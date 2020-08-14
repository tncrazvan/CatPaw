<?php
namespace com\github\tncrazvan\catpaw\tools;

class Process{
    /**
     * Spawn a new process and send headers through the stdin stream.
     * @param string $cmd process cmd to execute
     * @param resource stdin stream. Use this to send data to the process.
     */
    public static function spawn(string $cmd,array $headers = [],?\Closure $callback = null){
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            //2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
        );

        $headers = \json_encode($headers);
        $length = \strlen($headers);
        $length_str = ''.$length;
        $length_str_length = \strlen($length_str);

        
        $process = \proc_open($cmd,$descriptorspec,$streams);
        \fwrite($streams[0],$length_str.','.$headers,$length_str_length+1+$length);
        //\fwrite($streams[0],$contents,strlen($contents));
        /*\fwrite($streams[0],$length_str,$length_str_length);
        \fwrite($streams[0],',',1);
        \fwrite($streams[0],$headers,$length);*/
        if($callback !== null){
            $callback($streams[0]);
        }
    }
}