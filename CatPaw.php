<?php
namespace com\github\tncrazvan\CatPaw;

use com\github\tncrazvan\CatPaw\Tools\Server;
use com\github\tncrazvan\CatPaw\Tools\Session;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\CatPaw\Tools\Minifier;
use com\github\tncrazvan\CatPaw\Http\HttpEventListener;
use com\github\tncrazvan\CatPaw\WebSocket\WebSocketManager;

class CatPaw extends Server{
    private $socket,
            $binding,
            $listening;
    
    /**
     * @param &$args This is the input array. The first element of this array should point to the settings json file, for example "http.json".
     * @param $intercept This is an anonymus function that will be called before the server socket is created and after the socket context is created.
     * This function will be fed one parameters: <b>$context</b>, this is the stream context of the server socket (which, by the way, is not created yet at this point).
     * Modify this context to suit your needs.
     * @param $certificate This is the certificate filename. Note that the path is relative to the settings (http.json) file.
     * @param $password This is the passphrase of your certificate.
     */
    public function __construct(&$args,$intercept=null) {
        $protocol="tcp";
        $argsLength = count($args);
        if($argsLength > 1 && file_exists($args[1])){

            //SETTINGS INIT
            $settings = Server::init($args[1]);

            //MINIFIER INIT
            /*
            try{
                $pid = pcntl_fork();
                    if ($pid == -1) {
                        die('could not fork');
                } else if ($pid) {
                    //PARENT
                } else {
                    //CHILD
                    while(true){
                        $assets = json_decode(\file_get_contents(Server::$webRoot."/assets.json"),true);
                        $minifier = new Minifier(Server::$webRoot,$assets);
                        $minifier->minify();
                        \usleep(Server::$minifier["sleep"]*1000);
                    }
                }
            }catch(\Exception $e){
                die("Something went wrong while forking...");
            }
            */

            $context = stream_context_create();
            //check if SSL certificate file is specified
            if(Server::$certificateName !== ""){
                //use the SSL certificate
                stream_context_set_option($context, 'ssl', 'local_cert', Server::$certificateName);
                if(isset($settings["certificate"]["privateKey"]))
                    stream_context_set_option($context, 'ssl', 'local_pk', Server::$certificatePrivateKey);
                stream_context_set_option($context, 'ssl', 'passphrase', Server::$certificatePassphrase);
                stream_context_set_option($context, 'ssl', 'cyphers', 2);
                stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
                stream_context_set_option($context, 'ssl', 'verify_peer', false);
            }
            //let the developer intercept the stream context
            if($intercept !== null) $intercept($context);
            // Create the server socket
            $this->socket = stream_socket_server(
                $protocol.'://'.Server::$bindAddress.':'.Server::$port,
                $errno,
                $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
                $context
            );
            if(Server::$certificateName !== "")
                stream_socket_enable_crypto($this->socket, false);
            if ($this->socket === false) throw new \Exception("$errstr ($errno)\n");
            $this->listening=true;

            //check if developer allows ramdisk for session storage
            if(Server::$ramSession["allow"]){
                //if ramdisk is allowed, mount a new one
                
                //Session::mount();
                
                //WARNING: ramdisk will remain mounted untill the next session is started
                //which means the ramdisk could be alive after the server shuts down.
                //you can run ./sessionMount.php to umount the current session
            }else{
                //Session::init();
            }

            $this->start();
        }else{
            throw new \Exception ("\nSettings json file doesn't exist\n");
        }
    }
    
    private $clients = [];
    /**
     * Start listening for requests.
     * @return void
     */
    private function start():void{
        //if the server is not supposed to listen for requests, kill the server.
        if (!$this->listening) return;
        //push the server socket (the one listening) to the clients array
        array_push($this->clients, $this->socket);
        echo "\nServer started.\n";
        //as long as the server is supposed to listen...
        while($this->listening){
            /*
                create a copy of the clients array
                this is needed so that we avoid changing the actualy array
                it's critical the original array doesn't get intercepted and changedù
            */
            $copy = $this->clients;
            $write = NULL;
            $except = NULL;
            $tv_sec = 0;
            //check if something interesting is going on with clients array ($copy)
            if (@stream_select($copy, $write, $except, $tv_sec, self::$sleep) < 1){
                /*
                    stream_select returns the number of connections acquired,
                    so skip if it returns < 1
                */
                continue;
            }
            
            // check if there is a client trying to connect
            if (in_array($this->socket, $copy)) {
                //accept the client, and add him to the $clients array
                $this->clients[] = $client = stream_socket_accept($this->socket,"-1",$remoteIp);
                //get the array key of the server socket
                $key = array_search($this->socket, $copy);
                //remove it from the copied array so that watchClients() doesn't have to
                unset($copy[$key]);
            }
            //watch and serve clients from the copied array
            //$this->watchClients($copy);
            try{
                $this->watchClientsNonBlocking($copy);
            }catch(\Exception $e){
                echo $e->getTraceAsString()."\n";
            }
        }
        //close socket when server stops listening
        fclose($this->socket);
        echo "\nServer stopped.\n";
    }
    
    private function watchClientsNonBlocking(array &$copy):void{
        foreach($copy as &$client){
            //get the array key of the client
            $key = array_search($client, $copy);
            //check for certificate
            if(Server::$certificateName !== ""){
                //block the connection until SSL is done.
                stream_set_blocking($client, true);
                //enable socket crypto method
                @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER);
            }
            
            $listener = new HttpEventListener($client, $this->clients);
            $listener->run();
                
            //check if certificate is specified, assume the socket has been blocked and then unblock it
            if(Server::$certificateName !== "")
                stream_set_blocking($client, false);
            //exit;

            unset($copy[$key]);
            unset($this->clients[$key]);
        }
        //read websockets
        while(WebSocketManager::$connections != null && !WebSocketManager::$connections->isEmpty()){
            $e = WebSocketManager::$connections->readNode(1);
            $e->push();
            $e->read();
            if(WebSocketManager::$connections->isLast($e)){
                echo "breaking\n";
                break;
            }
        }
        
    }

    private function watchClients(array &$copy):void{
        foreach($copy as &$client){
            //get the array key of the client
            $key = array_search($client, $copy);
            $pid = pcntl_fork();
            /*NOTE: parent and children don't share the same memory space, 
            they copy the memory of the parent process in a isolated environment until they die,
            so don't load the main process with too much data, or it won't be long
            before your memory gets filled due to excessive copies.
            */
            
            if ($pid === -1) {
                /*
                    fork request failed,
                    removed its pointer from the clients array and 
                    the copy (unsetting the copy is not required) and
                    kill the current socket handler
                */
                unset($copy[$key]);
                unset($this->clients[$key]);
                @fclose($client);
            } else if ($pid) {
                /*
                    we are the parent,
                    removed socket pointer from the clients array and the
                    the copied array (unsetting the copied one is not required) and
                    kill the current socket handler
                */
                unset($copy[$key]);
                unset($this->clients[$key]);
                @fclose($client);
            } else {
                //check if certificate is specified
                if(Server::$certificateName !== ""){
                    //block the connection until SSL is done.
                    stream_set_blocking ($client, true);
                    //enable socket crypto method
                    @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER);
                }
                /*
                    Make a new request listener.
                    At this point WebSocket requests are still treated as HTTP requests,
                    so they're treated as HttpEvents.

                    They'll morph into actual WebSocketEvents only after the 
                    handshake is completed.
                */
                $listener = new HttpEventListener($client, $this->clients);
                $listener->run();
                
                //check if certificate is specified and unblock the socket
                if(Server::$certificateName !== "")
                    stream_set_blocking ($client, false);
                exit;
            }
        }
    }
    
    /**
     * Check if the server is listening for requests.
     * @return bool true if the server is listening, otherwise false.
     */
    public function isOnline():bool{
        return $this->listening;
    }
    
    /**
     * Stop the server from listening for requests.
     * @return void
     */
    public function stop():void{
        $this->listening = false;
    }
}
