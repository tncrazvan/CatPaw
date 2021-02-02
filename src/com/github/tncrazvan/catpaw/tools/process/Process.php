<?php
namespace com\github\tncrazvan\catpaw\tools\process;

use Opis\Closure\SerializableClosure;

class Process{
    /**
     * Spawn a new process and send headers through the stdin stream.
     * @param cmd cmd to execute.
     * @param headers an array of headers to pass to the new process standard input.
     * @param call a callback \Closure that will be invoked once the process is ready to start.<br />
     * This callback will be passed the standard input stream of the process which you can use to write data to the process.
     * @param void
     */
    public static function cmd(string $cmd,array $headers = [],?\Closure $call = null):void{
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
        if($call !== null){
            $call($streams[0]);
        }
    }
}