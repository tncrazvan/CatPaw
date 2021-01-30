<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\http\HttpCommit;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\Caster;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\XMLSerializer;

abstract class HttpEventManager extends EventManager{
    public \Closure $callback;
    public ?HttpEventOnClose $onClose = null;
    public bool $defaultHeader = true;
    public static array $connections = [];
    private \SplDoublyLinkedList $commits;
    public ?\Generator $generator = null;
    public ?array $params;
    public bool $cbinit = false;
    public bool $paramsinit = false;
    private $_first_commit = true;

    public function __construct(){
        $_first_commit = &$this->_first_commit;
        $this->_commit_fn = function($data,&$dataByReference = null) use(&$scope,&$_first_commit){
            
            if($data === false)
                if($_first_commit){
                    //$this->funcheck($dataByReference);
                    if(!$dataByReference instanceof \Generator){
                        $this->dispatch($dataByReference);
                    }else {
                        $this->generator = &$dataByReference;
                        $this->generator->valid();
                    }
                }else{
                    $scope->commit($dataByReference);
                }
            else
                if($_first_commit){
                    //$this->funcheck($data);
                    if(!$data instanceof \Generator){
                        $this->dispatch($data);
                    }else {
                        $this->generator = &$data;
                        $this->generator->valid();
                    }  
                }else{
                    $scope->commit($data);
                }
            
            $_first_commit = false;
        };
    }

    public $responseObject = null;

    public function initParams():bool{
        $this->paramsinit = true;
        $message = '';
        $valid = true;
        $this->params = &$this->calculateParameters($message,$valid);
        if(($this->listener->issetProperty('http-consumer') && $this->listener->getProperty('http-consumer')) && !$this->_consumer_provided){
            exit("HttpConsumer not provided for resource {$this->listener->getResource()}.\nPlease inject an HttpConsumer parameter in your callback.\n");
        }
        if(!$valid){
            $this->commits = new \SplDoublyLinkedList();
            $this->responseObject = new HttpResponse([
                "Status"=>Status::BAD_REQUEST
            ],$message);
            $this->dispatch($this->responseObject);
        }
        
        return $valid;
    }

    public function initCallback():bool{
        $this->cbinit = true;
        try{
            $this->commits = new \SplDoublyLinkedList();
            $this->responseObject = \call_user_func_array($this->callback,$this->params);
            return true;
        }catch(\TypeError $ex){
            $this->responseObject = new HttpResponse([
                "Status"=>Status::INTERNAL_SERVER_ERROR
            ],$ex->getMessage()."\n".$ex->getTraceAsString());
        }catch(HttpEventException $ex){
            $this->responseObject = new HttpResponse([
                "Status"=>$ex->getStatus()
            ],$ex->getMessage()."\n".$ex->getTraceAsString());
        }catch(\Exception $ex){
            $this->responseObject = new HttpResponse([
                "Status"=>Status::INTERNAL_SERVER_ERROR
            ],$ex->getMessage()."\n".$ex->getTraceAsString());
        }
        //$this->funcheck($this->responseObject);
        $this->dispatch($this->responseObject);
        return false;
    }

    public function run():bool{
        if($this->autocommit){
            //$this->funcheck($this->responseObject);
            if(!$this->responseObject instanceof \Generator){
                $this->dispatch($this->responseObject);
            }else {
                $this->generator = &$this->responseObject;
                $error = true;
                try{
                    $this->generator->valid();
                    $error = false;
                }catch(\TypeError $ex){
                    $response = new HttpResponse([
                        "Status"=>Status::INTERNAL_SERVER_ERROR
                    ],$ex->getMessage()."\n".$ex->getTraceAsString());
                    $this->responseObject = &$response;
                }catch(HttpEventException $ex){
                    $response = new HttpResponse([
                        "Status"=>$ex->getStatus()
                    ],$ex->getMessage()."\n".$ex->getTraceAsString());
                    $this->responseObject = &$response;
                }catch(\Exception $ex){
                    $response = new HttpResponse([
                        "Status"=>Status::INTERNAL_SERVER_ERROR
                    ],$ex->getMessage()."\n".$ex->getTraceAsString());
                    $this->responseObject = &$response;
                }
                if($error){
                    $this->dispatch($this->responseObject);
                    return false;
                }
            }
        }
        return true;
    }
    

    private function adaptHeadersAndBody(array &$accepts,&$body):void{
        $count_accepts = \count($accepts);
        
        if($this->reflection_method !== null && !$this->serverHeaders->has(("Content-Type")) 
            && ( 
                ($produces = Produces::findByMethod($this->reflection_method))
                || ($produces = Produces::findByClass($this->reflection_class))
            )
        ){
            $produced = \preg_split('/\s*,\s*/',\strtolower($produces->getProducedContentTypes()));
        }else{
            $produced = \preg_split('/\s*,\s*/',$this->serverHeaders->get("Content-Type"));
        }

        if($count_accepts === 1 && \count($produced) === 1 && $accepts[0] === '' && $produced[0] === '')
            return;

        foreach($accepts as &$accept){
            if(\in_array($accept,$produced)){
                switch($accept){
                    case 'application/json':
                        $body = \json_encode($body);
                        if(!$this->serverHeaders->has(("Content-Type")))
                            $this->serverHeaders->set("Content-Type",$accept);
                    return;
                    case 'application/xml':
                    case 'text/xml':
                        if(\is_array($body)){
                            $body = XMLSerializer::generateValidXmlFromArray($body);
                        }else{
                            $cast = Caster::cast($body,\stdClass::class);
                            $body = XMLSerializer::generateValidXmlFromObj($cast);
                        }
                        if(!$this->serverHeaders->has(("Content-Type")))
                            $this->serverHeaders->set("Content-Type",$accept);
                    return;
                }
            }
        }

        if(isset($produced[0])){
            $this->serverHeaders->set("Content-Type",$produced[0]);
            return;
        }

        $this->setResponseStatus(Status::BAD_REQUEST);
        $this->serverHeaders->set("Content-Type","text/plain");
        $body = "This resource produces types [".\implode(',',$produced)."], which don't match with any types accepted by the request [".\implode(',',$accepts)."].";
    }

    public function dispatch(&$responseObject){
        $accepts = \explode(",",\trim($this->getRequestHeader("Accept")));
        

        if(!$responseObject instanceof HttpResponse){
            $this->adaptHeadersAndBody($accepts,$responseObject);
            $responseObject = new HttpResponse($this->serverHeaders,$responseObject);
        }else{
            $this->adaptHeadersAndBody($accepts,$responseObject->getBody());
        }
        

        $responseHeader = $responseObject->getHeaders();
        $responseHeader->initialize($this);
        $responseHeader->mix($this->serverHeaders);

        if(!$responseHeader->has("Content-Length")){
            $length = \strlen($responseObject->getBody());
            $responseHeader->set("Content-Length",''.$length);
        }

        $chunks = \str_split($responseObject->toString(),$this->listener->getSharedObject()->getHttpMtu());

        for($i=0,$len=\count($chunks);$i<$len;$i++){
            $this->commit($chunks[$i]);
        }
    }

    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }

    public function commit(&$data):void{
        $this->commits->push(new HttpCommit($data));
    }
    
    public function push(int $count=-1):bool{
        $i = 0 ;
        $this->commits->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
        for ($this->commits->rewind();$this->commits->valid();$this->commits->next()) {
            $httpCommit = $this->commits->current();
            $contents = $httpCommit->getData();
            $result = $this->fwrite_stream($contents);
            if(!$result){
                $this->close();
                $this->listener->getSharedObject()->unsetHttpConnectionsEntry($this->requestId);
                $this->uninstall();
                return false;
            }
            $i++;
            if($count > 0 && $i >= $count)
                break;
        }

        //No more commits to send, end connection.
        $isEmpty = $this->commits->isEmpty();
        if($isEmpty){
            $this->close();
            $this->listener->getSharedObject()->unsetHttpConnectionsEntry($this->requestId);
            $this->uninstall();
        }
        return $isEmpty;
    }
}
