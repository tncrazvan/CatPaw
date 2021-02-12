<?php
namespace com\github\tncrazvan\catpaw;

use Exception;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\tools\Caster;
use com\github\tncrazvan\catpaw\tools\helpers\Route;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\process\Process;
use com\github\tncrazvan\catpaw\tools\XMLSerializer;

class HttpInvoker{
    public function __construct(
        private \React\Http\Server $server
   ) {}

    public function &invoke(
        ServerRequestInterface $request,
        string $method,
        string $path,
        array $params_map,
   ):Response{
        $function = isset(Route::$reflection_functions[$method][$path])?
                    Route::$reflection_functions[$method][$path]:null;

        if($function)
            $reflection_parameters = isset(Route::$reflection_functions_parameters[$method][$path])?
                                     Route::$reflection_functions_parameters[$method][$path]:null;
        else
            $reflection_parameters = isset(Route::$reflection_methods_parameters[$method][$path])?
                                     Route::$reflection_methods_parameters[$method][$path]:null;

            
        $attr_path_params = isset(Route::$path_params[$method][$path])?
                            Route::$path_params[$method][$path]:null;
        $attr_headers = isset(Route::$reflection_methods_parameters_attributes_headers[$method][$path])?
                        Route::$reflection_methods_parameters_attributes_headers[$method][$path]:null;
        $attr_status = isset(Route::$reflection_methods_parameters_attributes_status[$method][$path])?
                       Route::$reflection_methods_parameters_attributes_status[$method][$path]:null;
        $attr_consumes = $function?null:Route::$reflection_methods_attributes_consumes[$method][$path];
        $attr_produces = $function?null:Route::$reflection_methods_attributes_produces[$method][$path];
        $status = new Status();
        $headers = [];
        $params = array();
        $i = 0;
        if($reflection_parameters)
            foreach($reflection_parameters as $reflection_parameter){
                if($reflection_parameter instanceof \ReflectionParameter){
                    $this->inject(
                        $request,
                        $headers,
                        $status,
                        $params_map,
                        $attr_path_params,
                        $attr_headers,
                        $attr_status,
                        $reflection_parameter,
                        $params,
                        $i
                );
                }
                $i++;
            }

        $reflection_method = isset(Route::$reflection_methods[$method][$path])?
                             Route::$reflection_methods[$method][$path]:null;

        if($function){
            $body = (new \ReflectionFunction($function))->invokeArgs($params);
        }else{
            $reflection_class = isset(Route::$reflection_class[$method][$path])?
                                Route::$reflection_class[$method][$path]:null;

            $reflection_method->setAccessible(true);
            $instance = Factory::make($reflection_class->getName());
            //$fname = $reflection_method->getName();
            $body =  $reflection_method->invokeArgs($instance,$params);
            $reflection_method->setAccessible(false);
        }
        
        if($body instanceof Response)
            return $body;

        if($attr_produces && $attr_produces instanceof Produces && !isset($headers['Content-Type'])){
            $headers['Content-Type'] = $attr_produces->getProducedContentTypes();
        }

        $status = $status->getCode();
        $accepts = \explode(",",($request->getHeaderLine("Accept")));
        if($body === null)
            $body = '';
            
        static::adaptResponse(
            $method,
            $path,
            $status,
            $headers,
            $accepts,
            $body,
            $reflection_method,
            $function,
        );

        $response = new Response($status,$headers,$body?:'');

        return $response;
    }

    private static function adaptResponse(
        string $method,
        string $path,
        int &$status,
        array &$headers,
        array &$accepts,
        mixed $body,
        ?\ReflectionMethod $reflection_method,
        ?\Closure $function,
    ):void{
        if(
            (
                $reflection_method 
                && !isset($headers['Content-Type'])
                && 
                (
                    $produces = isset(Route::$reflection_methods_attributes_produces[$method][$path])?
                                Route::$reflection_methods_attributes_produces[$method][$path]:null
                    ||
                    $produces = isset(Route::$reflection_class_attributes_produces[$method][$path])?
                                Route::$reflection_class_attributes_produces[$method][$path]:null
                )
            )
            ||
            (
                $function 
                && !isset($headers['Content-Type'])
                && 
                (
                    $produces = isset(Route::$reflection_functions_attributes_produces[$method][$path])?
                                Route::$reflection_functions_attributes_produces[$method][$path]:null
                )
            )
        ){
            $produced = \preg_split('/\s*,\s*/',\strtolower($produces->getProducedContentTypes()));
        }else{
            $produced = \preg_split('/\s*,\s*/',isset($headers['Content-Type'])?$headers['Content-Type']:'');
        }

        $produced = array_filter($produced,fn($item)=>$item!=='');

        $cproduced = \count($produced);
        if($cproduced === 0)
            $produced = ["text/plain","application/json","application/xml","text/xml","text/html"];
        

        if(\count($accepts) === 1 && \count($produced) === 1 && $accepts[0] === '' && $produced[0] === '')
            return;

        foreach($accepts as &$accept){
            if('*/*' === $accept || '' === $accept || \in_array($accept,$produced)){
                static::transform($body,$headers,$accept,$produced);
                return;
            }
        }

        $status = 400; //(Status::BAD_REQUEST);
        $headers['Content-Type'] = 'text/plain';
        $body = 'This resource produces types ['.\implode(',',$produced).'], which don\'t match with any types accepted by the request ['.\implode(',',$accepts).'].';
    }


    private static function transform(
        &$body,
        array &$headers,
        string &$ctype,
        array &$fallback_ctypes
    ):void{
        switch($ctype){
            case 'application/json':
                $body = \json_encode($body);
                $headers['Content-Type'] = $ctype;
            return;
            case 'application/xml':
            case 'text/xml':
                if(\is_array($body)){
                    $body = XMLSerializer::generateValidXmlFromArray($body);
                }else{
                    $cast = Caster::cast($body,\stdClass::class);
                    $body = XMLSerializer::generateValidXmlFromObj($cast);
                }
                $headers['Content-Type'] = $ctype;
            return;
            case 'text/plain':
                if(\is_array($body) || \is_object($body))
                    $body = \json_encode($body);
                
                $headers['Content-Type'] = 'text/plain';
            return;
            case '*/*':
            case '':
                if(\is_array($body) || \is_object($body)){
                    $body = \json_encode($body);
                    
                    if(\in_array('application/json',$fallback_ctypes))
                        $headers['Content-Type'] = 'application/json';
                    else
                        $headers['Content-Type'] = 'text/plain';
                }else 
                    $headers['Content-Type'] = 'text/plain';
                
            return;
            default:
                if(\is_array($body) || \is_object($body))
                    $body = \json_encode($body);
                $headers['Content-Type'] = $ctype;
            return;
        }
    }

    private function inject(
        ServerRequestInterface $request,
        array &$headers,
        Status &$status,
        array &$params,
        ?array $attr_path_params,
        ?array $attr_headers,
        ?array $attr_status,
        \ReflectionParameter $reflection_parameter,
        array &$p,
        int $i
   ):void{
        $name = $reflection_parameter->getName();
        $classname = $reflection_parameter->getType()->getName();
        static $param = null;
        if($attr_path_params && isset($attr_path_params[$name])){
            switch($classname){
                case 'bool':
                    $p[$i] = \filter_var($params[$name] || false, FILTER_VALIDATE_BOOLEAN);
                break;
                case 'string':
                    $p[$i] = &$params[$name] || null;
                break;
                case 'int':
                    if(isset($params[$name])){
                        if(\is_numeric($params[$name]))
                            $p[$i] = (int) $params[$name];
                        else{
                            throw new Exception('Parameter {'.$name.'} was expected to be numeric, but non numeric value has been provided instead:'.$params[$name]);
                        }
                    }else{
                        $p[$i] = &$param;
                    }
                break;
                case 'float':
                    $p[$i] = (float) $params[$name];
                break;
                default:
                    $p[$i] = null;
                break;
            }//switch
        }//is path param
        else{
            switch($classname){
                case 'array':
                    if($attr_headers && isset($attr_headers[$name])){
                        $p[$i] = &$headers;
                    }
                    break;
                case Status::class:
                    if($attr_status && isset($attr_status[$name])){
                        $p[$i] = &$status;
                    }
                    break;
                default:
                    $p[$i] = null;
                break;
            }
        }//is not path param but is param
    }//void
}