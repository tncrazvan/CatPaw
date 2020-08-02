<?php
namespace com\github\tncrazvan\catpaw;

use Closure;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\tools\Session;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Status;

class CatPaw{
    private $socket,
            $listening,
            $minifyOperation=null,
            $argv;
        
    public $so;
    
    /**
     * @param &$argv[1] This is the name of the config json file, for example "http.json".
     * @param $intercept This is an anonymus function that will be called before the server socket is created and after the socket context is created.
     * This function will be fed one parameters: <b>$context</b>, this is the stream context of the server socket (which, by the way, is not created yet at this point).
     * Modify this context to suit your needs.
     * @param $certificate This is the certificate filename. Note that the path is relative to the settings (http.json) file.
     * @param $password This is the passphrase of your certificate.
     */
    public function __construct(&$argv,$beforeStart=null) {
        $this->argv = $argv;
        $protocol="tcp";
        if(file_exists($argv[1])){
            //creating SharedObject
            $so = new SharedObject($argv[1]);
            $this->so = $so;
            //write down initial args
            \file_put_contents($so->dir.'/args',$argv[1]);
            //creating context
            $context = stream_context_create();
            //check if SSL certificate file is specified
            if($so->certificateName !== ""){
                //use the SSL certificate
                stream_context_set_option($context, 'ssl', 'local_cert', $so->certificateName);
                if(isset($so["certificate"]["privateKey"]))
                    stream_context_set_option($context, 'ssl', 'local_pk', $so->certificatePrivateKey);
                stream_context_set_option($context, 'ssl', 'passphrase', $so->certificatePassphrase);
                stream_context_set_option($context, 'ssl', 'cyphers', 2);
                stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
                stream_context_set_option($context, 'ssl', 'verify_peer', false);
            }
            //let the developer intercept the stream context
            if($beforeStart !== null) $beforeStart($context,$this->minifyOperation);
            // Create the server socket
            $this->socket = stream_socket_server(
                $protocol.'://'.$so->bindAddress.':'.$so->port,
                $errno,
                $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
                $context
            );
            if($so->certificateName !== "")
                stream_socket_enable_crypto($this->socket, false);
            if ($this->socket === false) throw new \Exception("$errstr ($errno)\n");
            $this->listening=true;

            //check if developer allows ramdisk for session storage
            if($so->ramSession["allow"]){
                //if ramdisk is allowed, mount a new one
                Session::mount($so);
                
                //WARNING: ramdisk will remain mounted untill the next session is started
                //which means the ramdisk could be alive after the server shuts down.
                //you can run ./sessionMount.php to umount the current session
            }else{
                Session::init($so);
            }
        }else{
            throw new \Exception ("\nConfig file \"{$this->argv[1]}\" doesn't exist\n");
        }
    }

    public function whileListening(Closure $action){
        $this->whileListeningWorking = false;
        $this->whileListeningOperation = $action;
        $this->lastWhileWorkingOperationTime = microtime(true)*1000;
    }
    private $lastMinifyTime = 0;
    private $lastWhileWorkingOperationTime = false;
    private $clients = [];
    /**
     * Start listening for requests.
     * @return void
     */
    public function listen(Closure $action=null,int $actionIntervalMS=1000):void{
        //if the server is not supposed to listen for requests, kill the server.
        if (!$this->listening) return;
        
        $lastTime = 0;
        $actionWorking = false;

        //push the server socket (the one listening) to the clients array
        array_push($this->clients, $this->socket);
        echo "\nServer started.\n";
        //as long as the server is supposed to listen...
        while($this->listening){
            if($action !== null && (microtime(true)*1000) - $lastTime >= $actionIntervalMS && !$actionWorking) {
                $actionWorking = true;
                $action();
                $lastTime = microtime(true)*1000;
                $actionWorking = false;
            }
            
            /**
             * Listen for web sockets.
             * Read incoming messages and push pending commits.
            */
            if($this->so->websocketConnections != null && !$this->so->websocketConnections->isEmpty()){
                $node = $this->so->websocketConnections->getFirstNode();
                while($node !== NULL){
                    $e = $node->readNode();
                    $e->push();
                    $e->read();
                    $node = $node->next;
                }
            }

            /**
             * Listen for http sockets.
             * Read incoming messages and push pending commits.
            */
            if($this->so->httpConnections != null && !$this->so->httpConnections->isEmpty()){
                $node = $this->so->httpConnections->getFirstNode();
                while($node !== NULL){
                    $e = $node->readNode();
                    $e->push();
                    $node = $node->next;
                }
            }

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
            if (@stream_select($copy, $write, $except, $tv_sec, $this->so->sleep) < 1){
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
            stream_set_blocking($client, false);
            //get the array key of the client
            $key = array_search($client, $copy);
            //check for certificate
            if($this->so->certificateName !== ""){
                //block the connection until SSL is done.
                stream_set_blocking($client, true);
                //enable socket crypto method
                @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER);
            }

            //check if certificate is specified, assume the socket has been blocked and then unblock it
            if($this->so->certificateName !== "")
                stream_set_blocking($client, false);

            $listener = new HttpEventListener($client, $this->so);
            $listener->run();
            
            unset($copy[$key]);
            unset($this->clients[$key]);
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
                if($this->so->certificateName !== ""){
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
                $listener = new HttpEventListener($client, $this->so);
                $listener->run();
                
                //check if certificate is specified and unblock the socket
                if($this->so->certificateName !== "")
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
