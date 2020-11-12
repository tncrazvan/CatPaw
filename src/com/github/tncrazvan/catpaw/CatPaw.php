<?php
namespace com\github\tncrazvan\catpaw;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\tools\Session;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

/**
 * @param argv This is the name of the config json file, for example "http.json".
 * @param beforeStart This is a closure that will be executed right before the server starts.
 * This Closure will be passed the context of the socket stream as a parameter.
 */
class CatPaw{
    private $socket;
    private bool $listening;
    private SharedObject $so;
    private $client;
    public function __construct(array $settings,int $count = 0,?\Closure $beforeStart=null) {
        $protocol="tcp";
        //creating SharedObject
        $so = new SharedObject($settings,$count > 0);
        $this->so = $so;
        //creating context
        $context = stream_context_create();
        //check if SSL certificate file is specified
        if($so->getCertificateName() !== ""){
            //use the SSL certificate
            stream_context_set_option($context, 'ssl', 'local_cert', $so->getCertificateName());
            if(isset($so["certificate"]["privateKey"]))
                stream_context_set_option($context, 'ssl', 'local_pk', $so->getCertificatePrivateKey());
            stream_context_set_option($context, 'ssl', 'passphrase', $so->getCertificatePrivatePassphrase());
            stream_context_set_option($context, 'ssl', 'cyphers', 2);
            stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
        }
        //intercept the stream context
        if($beforeStart !== null) $beforeStart($context);
        // Create the server socket
        $this->socket = stream_socket_server(
            $protocol.'://'.$so->getBindAddress().':'.$so->getPort(),
            $errno,
            $errstr,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            $context
        );
        if($so->getCertificateName() !== "")
            stream_socket_enable_crypto($this->socket, false);
        if ($this->socket === false) throw new \Exception("$errstr ($errno)\n");
        $this->listening=true;

        //check if ramdisk is allowed for session storage
        if($so->getRamSession()["allow"]){
            //if ramdisk is allowed, try mount a new one
            Session::mount($so);
            //WARNING: ramdisk will remain mounted untill the next session is started
            //which means the ramdisk could be alive after the server shuts down.
            //you can run ./sessionMount.php to umount the current session
        }else{
            Session::init($so);
        }
    }


    private function &read(HttpEventListener $listener,int $chunkSize){
        $fragmentSize = $chunkSize;
        $read = \fread($this->client, $fragmentSize);
        if($read !== ''){
            $listener->setEmptyFails(0);
            $len = \strlen($read);
            while($len < $chunkSize){
                if($len + $fragmentSize > $chunkSize){
                    $fragmentSize = $chunkSize - $len;
                }
                $extra = \fread($this->client, $fragmentSize);
                if($extra === false || $extra === '') break;
                $len += \strlen($extra);
                $read .= $extra;
            }
        }else
            $listener->increaseEmptyFails();
        /*if($listener->getEmptyFails() > 64 && $read === ''){
            $read = false;
        }*/
        return $read;
    }


    private $clients = [];
    /**
     * Start listening for requests.
     * @param action callback action that will be called every N milliseconds, where N is the return value of the callback itself.
     * @return void
     */
    public function listen(?\Closure $action=null):void{
        //if the server is not supposed to listen for requests, kill the server.
        if (!$this->listening) return;

        $actionIntervalMS=1000;
        $lastTime = 0;
        $actionWorking = false;

        //push the server socket (the one listening) to the clients array
        array_push($this->clients, $this->socket);
        echo "\nServer started.\n";

        
        $this->so->fixFordwardRecursion($this->so->getHttpEventsEntry("@forward"));

        //listen for connections
        while($this->listening){

            /**
             * Listen for queued http sockets and read incoming data.
            */
            foreach($this->so->getHttpQueue() as &$listener){
                $this->client = $listener->getClient();
                if(!$listener->httpConsumerStarted() && $listener->getActualBodyLength()+$listener->getSharedObject()->getHttpMtu() > $listener->getSharedObject()->getHttpMaxBodyLength()){
                    $delta = $listener->getSharedObject()->getHttpMaxBodyLength() - $listener->getActualBodyLength();
                    if($delta > 0)
                        $read = &$this->read($listener,$delta);
                    else 
                        $read = '';
                }else{
                    $read = &$this->read($listener,$listener->getSharedObject()->getHttpMtu());
                }

                if($read !== false){
                    if($listener->getContinuation() > 0){
                        $listener->increaseActualBodyLengthByValue(\strlen($read));

                        if($listener->httpConsumerStarted()){
                            $listener->runHttpLiveBodyInject($read);
                        }else{
                            $listener->input[1] .= $read;
                        }

                        if($listener->bodyLengthsMatch() || $listener->actualBodyLengthIsMaxed())
                            $listener->setCompleteBody(true);
                    }else{
                        if($listener->httpConsumerStarted()){
                            $listener->runHttpLiveBodyInject($read);
                        }
                        else if($listener->event === null)
                            $listener->input .= $read;
                        else
                            $listener->input[1] .= $read;
                    }

                    if($listener->event === null){
                        if($listener->findHeaders()){
                            [$isHttp,$isWebSOcket] = $listener->run();
                            if($isHttp){
                                $listener->setEventType(HttpEventListener::EVENT_TYPE_HTTP);
                                $listener->event = &HttpEvent::make($listener);
                                $listener->event->checkHttpConsumer();
                                //echo "serving HTTP $listener->path \n";
                            }else if($isWebSOcket){
                                $listener->setEventType(HttpEventListener::EVENT_TYPE_WEBSOCKET);
                                $listener->event = &WebSocketEvent::make($listener);
                                //echo "serving WS $listener->path \n";
                            }else{
                                $listener->getSharedObject()->unsetHttpQueueEntry($listener->getHash());
                            }
                        }
                    }

                    if($listener->getEventType() == HttpEventListener::EVENT_TYPE_HTTP){
                        if($listener->getProperty("http-consumer")){
                            if(!$listener->event->paramsinit){
                                if(!$listener->event->initParams()){
                                    $listener->getSharedObject()->unsetHttpQueueEntry($listener->getHash());
                                    $listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                    continue;
                                }
                            }

                            if(!$listener->event->cbinit){
                                if(!$listener->event->initCallback()){
                                    $listener->getSharedObject()->unsetHttpQueueEntry($listener->getHash());
                                    $listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                    continue;
                                }
                            }

                            if(!$listener->httpConsumerStarted()){
                                $listener->httpConsumerStart();
                                global $_EVENT;
                                $_EVENT = $listener->event;
                                if(!$_EVENT->run()){
                                    $listener->getSharedObject()->unsetHttpQueueEntry($listener->getHash());
                                    $_EVENT = null;
                                    $listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                    continue;
                                }
                                $_EVENT = null;
                            }
                        }
                    }
                }

                if($read === false || $listener->isCompleteBody()){
                    $listener->getSharedObject()->unsetHttpQueueEntry($listener->getHash());
                    if($listener->getEventType() == HttpEventListener::EVENT_TYPE_WEBSOCKET){
                        $listener->runWebSocketDefault();
                    }else if($listener->getEventType() == HttpEventListener::EVENT_TYPE_HTTP || $listener->getContinuation() > 0){
                        if($listener->httpConsumerStarted()){
                            if($listener->isCompleteBody()){
                                //$read = false;
                                $tmp = false;
                                $listener->runHttpLiveBodyInject($tmp);
                                //$listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                $listener->getSharedObject()->setHttpConnectionsEntry($listener->event->getRequestId(),$listener->event);
                            }else
                                if(!$listener->runHttpLiveBodyInject($read)){
                                    //$listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                    $listener->getSharedObject()->setHttpConnectionsEntry($listener->event->getRequestId(),$listener->event);
                                }
                        }else if($listener->isCompleteBody()){
                            if(!$listener->event->initParams()){
                                //$listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                                $listener->getSharedObject()->setHttpConnectionsEntry($listener->event->getRequestId(),$listener->event);
                                continue;
                            }
                            $listener->runHttpDefault();
                            //$listener->getSharedObject()->getHttpConnections()[$listener->event->getRequestId()] = &$listener->event;
                            $listener->getSharedObject()->setHttpConnectionsEntry($listener->event->getRequestId(),$listener->event);
                        }else if($listener->getContinuation() === 0 && $listener->event !== null){
                            $listener->event->close();
                            $listener->event->uninstall();
                        }
                    }else if($listener->event !== null){
                        $listener->event->close();
                        $listener->event->uninstall();
                    }
                }
                if(($read === false) && $listener->getContinuation() > 0){
                    $listener->increaseFailedContinuations();
                }
                $read = null;
                $listener->increaseContinuation();
            }
            
            /**
             * Listen for http sockets and ush pending commits.
            */
            foreach($this->so->getHttpConnections() as &$e){
                if($e->generator){
                    if($e->generator->valid()){
                        global $_EVENT;
                        $_EVENT = $e;
                        $e->generator->next();
                        $_EVENT = null;
                    }else{
                        try{
                            $responseObject = $e->generator->getReturn();
                            $e->generator = null;
                            //$e->funcheck($responseObject);
                            $e->dispatch($responseObject);
                        }catch(\Exception $ex){
                            $e->generator = null;
                            if($e->push()){ //if there are not more commits to push...
                                $e = null; //set the object to null
                            }
                        }
                    }
                }else{
                    if($e->push()){ //if there are not more commits to push...
                        $e = null; //set the object to null
                    }
                }
            }
            
            
            /**
             * Listen for web sockets.
             * Read incoming messages and push pending commits.
            */
            foreach($this->so->getWebsocketConnections() as &$e){
                $e->push();
                $e->read();
            }


            if($action !== null && (microtime(true)*1000) - $lastTime >= $actionIntervalMS && !$actionWorking) {
                $actionWorking = true;
                $actionIntervalMS = $action($this->so);
                if($actionIntervalMS < 0) 
                    $this->listening = false;
                $lastTime = microtime(true)*1000;
                $actionWorking = false;
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
            if (@stream_select($copy, $write, $except, $tv_sec, $this->so->getSleep()) < 1){
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
            if($this->so->getCertificateName() !== ""){
                //block the connection until SSL is done.
                stream_set_blocking($client, true);
                //enable socket crypto method
                @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER);
            }

            //check if certificate is specified, assume the socket has been blocked and then unblock it
            if($this->so->getCertificateName() !== "")
                stream_set_blocking($client, false);
            //\stream_set_timeout($client,0,$this->so->getTimeout());
            $listener = new HttpEventListener($client, $this->so);
            $this->so->setHttpQueueEntry($listener->getHash(),$listener);
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
                if($this->so->getCertificateName() !== ""){
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
                if($this->so->getCertificateName() !== "")
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
