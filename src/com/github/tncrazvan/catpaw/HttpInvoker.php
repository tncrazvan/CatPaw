<?php
namespace com\github\tncrazvan\catpaw;

use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\http\Headers;
use Exception;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\tools\Caster;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\helpers\metadata\Meta;
use com\github\tncrazvan\catpaw\tools\XMLSerializer;

class HttpInvoker{

    public static function &invoke(
        ServerRequestInterface $request,
        string $http_method,
        string $http_path,
        array $http_params,
   ):Response{
        $__FUNCTION__ = Meta::FUNCTIONS[$http_method][$http_path];

        if($__FUNCTION__)
            $__ARGS__ = Meta::FUNCTIONS_ARGS[$http_method][$http_path];
        else
            $__ARGS__ = Meta::METHODS_ARGS[$http_method][$http_path];

            
        $__PATH_PARAMS__ = Meta::PATH_PARAMS[$http_method][$http_path];
        $__HEADERS__ = Meta::METHODS_ARGS_ATTRIBUTES[$http_method][$http_path][Headers::class];
        $__STATUS__ = Meta::METHODS_ARGS_ATTRIBUTES[$http_method][$http_path][Status::class];
        $__CONSUMES__ = $__FUNCTION__?null:Meta::METHODS_ATTRIBUTES[$http_method][$http_path][Consumes::class];
        $__PRODUCES__ = $__FUNCTION__?null:Meta::METHODS_ATTRIBUTES[$http_method][$http_path][Produces::class];
        
        if(!$__FUNCTION__){
            $__CONSUMES__ = Meta::CLASS_ATTRIBUTES[$http_method][$http_path][Consumes::class];
            $__PRODUCES__ = Meta::CLASS_ATTRIBUTES[$http_method][$http_path][Produces::class];
        }

        $status = new Status();
        $http_headers = [];
        $params = array();
        if($__ARGS__)
            foreach($__ARGS__ as $__ARG__){
                if($__ARG__ instanceof \ReflectionParameter){
                    static::inject(
                        $__PATH_PARAMS__,
                        $__HEADERS__,
                        $__STATUS__,
                        $__ARG__,
                        $status,
                        $http_headers,
                        $http_params,
                        $params
                    );
                }
            }

        $__METHOD__ = isset(Meta::METHODS[$http_method][$http_path])?
                             Meta::METHODS[$http_method][$http_path]:null;

        if($__FUNCTION__){
            $body = (new \ReflectionFunction($__FUNCTION__))->invokeArgs($params);
        }else{
            $__CLASS__ = isset(Meta::KLASS[$http_method][$http_path])?
                                Meta::KLASS[$http_method][$http_path]:null;

            $__METHOD__->setAccessible(true);
            $instance = Factory::make($__CLASS__->getName());
            //$fname = $reflection_http_method->getName();
            $body =  $__METHOD__->invokeArgs($instance,$params);
            $__METHOD__->setAccessible(false);
        }
        
        if($body instanceof Response)
            return $body;

        if($__PRODUCES__ && $__PRODUCES__ instanceof Produces && !isset($http_headers['Content-Type'])){
            $http_headers['Content-Type'] = $__PRODUCES__->getProducedContentTypes();
        }

        $http_status = $http_status_wrapper->getCode();
        $accepts = \explode(",",($request->getHeaderLine("Accept")));
        if($body === null)
            $body = '';
            
        static::adaptResponse(
            $__PRODUCES__,
            $http_status,
            $http_headers,
            $accepts,
            $body
        );

        $response = new Response($http_status,$http_headers,$body?:'');

        return $response;
    }

    private static function adaptResponse(
        ?Produces $__PRODUCES__,
        int &$http_status,
        array &$http_headers,
        array &$accepts,
        mixed &$body,
    ):void{
        if( !isset( $http_headers['Content-Type'] ) )
            $produced = \preg_split( '/\s*,\s*/',$__PRODUCES__->getProducedContentTypes() );
        else
            $produced = \preg_split( '/\s*,\s*/',$http_headers['Content-Type'] );
        

        $produced = array_filter($produced,fn($type)=>!empty($type));

        $cproduced = \count($produced);
        if($cproduced === 0 || $produced[0] === ''){
            $http_status = Status::NO_CONTENT;
            $http_headers['Content-Type'] = 'text/plain';
            $body = "This resource is not configured to produce any type of content.";
            return;
        }

        foreach($accepts as &$accepts_item){
            if('*/*' === $accepts_item || \in_array($accepts_item,$produced)){
                static::transform($body,$http_headers,$accepts_item,$produced);
                return;
            }
        }

        $http_status = Status::BAD_REQUEST;
        $http_headers['Content-Type'] = 'text/plain';
        $body = 'This resource produces types ['.\implode(',',$produced).'], which don\'t match with any types accepted by the request ['.\implode(',',$accepts).'].';
    }


    private static function transform(
        mixed &$body,
        array &$http_headers,
        string &$ctype,
        array &$fallback_ctypes
    ):void{
        switch($ctype){
            case 'application/json':
                $body = \json_encode($body);
                $http_headers['Content-Type'] = $ctype;
            return;
            case 'application/xml':
            case 'text/xml':
                if(\is_array($body)){
                    $body = XMLSerializer::generateValidXmlFromArray($body);
                }else{
                    $cast = Caster::cast($body,\stdClass::class);
                    $body = XMLSerializer::generateValidXmlFromObj($cast);
                }
                $http_headers['Content-Type'] = $ctype;
            return;
            case 'text/plain':
                if(\is_array($body) || \is_object($body))
                    $body = \json_encode($body);
                
                $http_headers['Content-Type'] = 'text/plain';
            return;
            case '*/*':
            case '':
                if(\is_array($body) || \is_object($body)){
                    $body = \json_encode($body);
                    
                    if(\in_array('application/json',$fallback_ctypes))
                        $http_headers['Content-Type'] = 'application/json';
                    else
                        $http_headers['Content-Type'] = 'text/plain';
                }else 
                    $http_headers['Content-Type'] = 'text/plain';
                
            return;
            default:
                if(\is_array($body) || \is_object($body))
                    $body = \json_encode($body);
                $http_headers['Content-Type'] = $ctype;
            return;
        }
    }

    private static function inject(
        ?array $__PATH_PARAMS__,
        ?array $__HEADERS__,
        ?array $__STATUS__,
        \ReflectionParameter $__ARG__,
        Status &$status,
        array &$http_headers,
        array &$http_params,
        array &$args
   ):void{
        $name = $__ARG__->getName();
        $classname = $__ARG__->getType()->getName();
        static $param = null;
        if($__PATH_PARAMS__ && isset($__PATH_PARAMS__[$name])){
            switch($classname){
                case 'bool':
                    $args[] = \filter_var($http_params[$name] || false, FILTER_VALIDATE_BOOLEAN);
                break;
                case 'string':
                    $args[] = &$http_params[$name] || null;
                break;
                case 'int':
                    if(isset($http_params[$name])){
                        if(\is_numeric($http_params[$name]))
                            $args[] = (int) $http_params[$name];
                        else{
                            throw new Exception('Parameter {'.$name.'} was expected to be numeric, but non numeric value has been provided instead:'.$http_params[$name]);
                        }
                    }else{
                        $args[] = &$param;
                    }
                break;
                case 'float':
                    $args[] = (float) $http_params[$name];
                break;
                default:
                    $args[] = null;
                break;
            }
        }
        else{
            switch($classname){
                case 'array':
                    if($__HEADERS__ && isset($__HEADERS__[$name])){
                        $args[] = &$http_headers;
                    }
                    break;
                case Status::class:
                    if($__STATUS__ && isset($__STATUS__[$name])){
                        $args[] = &$status;
                    }
                    break;
                default:
                    $args[] = null;
                break;
            }
        }
    }
}