<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\http\HttpCommit;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodCopy;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodDelete;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodGet;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodHead;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodLink;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodLock;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodOptions;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodPatch;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodPost;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodPropfind;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodPurge;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodPut;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodUnknown;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodUnlink;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodUnlock;
use com\github\tncrazvan\catpaw\http\methods\HttpMethodView;
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
                    $this->funcheck($dataByReference);
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
                    $this->funcheck($data);
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
        $this->responseObject = new HttpResponse([
            "Status"=>Status::BAD_REQUEST
        ],$message);
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
        $this->funcheck($this->responseObject);
        $this->dispatch($this->responseObject);
        return false;
    }

    public function run():bool{
        if($this->autocommit){
            $this->funcheck($this->responseObject);
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

    public function funcheck(&$responseObject){
        while($responseObject instanceof HttpEventHandler){
            if(
                $responseObject instanceof HttpMethodGet
                || $responseObject instanceof HttpMethodPost
                || $responseObject instanceof HttpMethodPut
                || $responseObject instanceof HttpMethodPatch
                || $responseObject instanceof HttpMethodDelete
                || $responseObject instanceof HttpMethodCopy
                || $responseObject instanceof HttpMethodHead
                || $responseObject instanceof HttpMethodOptions
                || $responseObject instanceof HttpMethodLink
                || $responseObject instanceof HttpMethodUnlink
                || $responseObject instanceof HttpMethodPurge
                || $responseObject instanceof HttpMethodLock
                || $responseObject instanceof HttpMethodUnlock
                || $responseObject instanceof HttpMethodPropfind
                || $responseObject instanceof HttpMethodView
                || $responseObject instanceof HttpMethodUnknown
            ){
                $lowermethod = \strtolower($this->getRequestMethod());
                if(\method_exists($responseObject,$lowermethod)){
                    $rfm = new \ReflectionMethod($responseObject,$lowermethod);
                    if (!$rfm->isPublic()) {
                        $responseObject = new HttpResponse([
                            "Status" => Status::METHOD_NOT_ALLOWED
                        ]);
                    }else {
                        $responseObject = $responseObject->$lowermethod();
                    }
                }else {
                    $responseObject = new HttpResponse([
                        "Status" => Status::METHOD_NOT_ALLOWED
                    ]);
                }
            }else{
                $responseObject = new HttpResponse([
                    "Status" => Status::METHOD_NOT_ALLOWED
                ]);
            }
        }
    }

    public function dispatch(&$responseObject){
        if(!$responseObject instanceof HttpResponse){
            $accepts = \explode(",",\trim($this->getRequestHeader("Accept")));
            foreach($accepts as &$acc){
                if($acc === 'application/json'){
                    $responseObject = \json_encode($responseObject);
                    if(!$this->serverHeaders->has(("Content-Type")))
                        $this->serverHeaders->set("Content-Type",$acc);
                }else if($acc === 'application/xml' || $acc === 'text/xml'){
                    if(\is_array($responseObject)){
                        $responseObject = XMLSerializer::generateValidXmlFromArray($responseObject);
                    }else{
                        $cast = Caster::cast($responseObject,\stdClass::class);
                        $responseObject = XMLSerializer::generateValidXmlFromObj($cast);
                    }
                    if(!$this->serverHeaders->has(("Content-Type")))
                        $this->serverHeaders->set("Content-Type",$acc);
                }
            }
            if(\is_object($responseObject))
            $responseObject = \json_encode($responseObject);

            $response = new HttpResponse($this->serverHeaders,$responseObject);
            $responseObject = null;
            $responseObject = &$response;
            
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
        for ($this->commits->rewind(); $this->commits->valid(); $this->commits->next()) {
            $httpCommit = $this->commits->current();
            $contents = &$httpCommit->getData();
            $length = \strlen($contents);
            $result = \fwrite($this->client, $contents, $length);
            if(!$result){
                $this->close();
                $this->listener->getSharedObject()->unsetHttpConnectionsEntry($this->requestId);
                $this->uninstall();
                break;
            }else if($result < $length){
                $extra = substr($contents,$result);
                $this->commit($extra);
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
