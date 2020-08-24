<?php
namespace com\github\tncrazvan\catpaw\tools;

class Stdin{
    public static ?array $headers = [];
    public static ?string $body = '';
    private static bool $started = false;

    /**
     * Reslve all the data passed in by the main process.
     * @return void
     */
    public static function resolve():void{
        if(self::$started) return;
        self::$started = true;
        $input = fopen('php://stdin','r');
        $start = microtime(true) * 1000;
        //$this->data = fread($input,intval($argv[1]));
        $byte = stream_get_contents($input,1);
        while($byte === false && $byte !== ''){
            $byte = stream_get_contents($input,1);
            $current = microtime(true) * 1000;
            if($current - $start > 1000 * 10){
                exit;
            }
        }
        $len = '';
        $offset = 0;
        while($byte !== ',' && $byte !== false && $offset <= 32){
            $len .= $byte;
            $offset++;
            $byte = stream_get_contents($input,1);
        }
        if($byte === false || $offset === 32 || !\is_numeric($len)){
            exit;
        }
        $len = \intval($len);
        $headers = stream_get_contents($input,$len);
        self::$headers = json_decode($headers,true);
        $read = stream_get_contents($input);
        while($read !== false && $read !== ''){
            self::$body .= $read;
            $read = stream_get_contents($input);
        }
    }
}