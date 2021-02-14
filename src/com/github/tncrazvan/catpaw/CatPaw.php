<?php
namespace com\github\tncrazvan\catpaw;

use com\github\tncrazvan\asciitable\AsciiTable;
use com\github\tncrazvan\catpaw\attributes\metadata\Meta;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\sessions\SessionManager;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\LoopInterface;

class CatPaw{
    private array $_map = [];
    public function __construct(
        private MainConfiguration $config,
        private ?\Closure $listen = null,
        private array $events = [],
        private ?\React\EventLoop\LoopInterface $loop = null,
        private ?\React\Http\Server $server = null
    ){
        $sm = new SessionManager($config);
        $invoker = new HttpInvoker($sm);

        foreach ( $this->events as $key => $event ) {
            if( $event instanceof HttpEvent ){
                if( !isset( $this->_map[$event->getMethod()] ) ){
                    $this->_map[$event->getMethod()][$event->getUri()] = $event->getClosure();
                }
            }
        }

        $loop = Factory::make(LoopInterface::class);
        if(!$loop){
            $loop = \React\EventLoop\Factory::create();


            $code = new AsciiTable(["width" => 70]);
            $import1 = Singleton::class;
            $import2 = LoopInterface::class;
            $import3 = EventLoopFactory::class;
            $code->add(
            "use $import1;\n"
            ."use $import2;\n"
            ."use $import3;\n\n"
            ."Singleton::\$map[LoopInterface::class] = Factory::create();");


            $code2 = new AsciiTable(["width"=>70]);
            $code2->add("#[Inject] LoopInterface \$loop;");

            $table = new AsciiTable([
                "width" => 80
            ]);
            $table->add("Note");
            $table->add(
                "\"".LoopInterface::class."\" could not be set as an application singleton.\n\n"
                .'Consider executing'
                ."\n\n"
                .$code->toString()
                ."\n\n"
                .'before starting the server, '
                ."this way you will be able to inject the loop \nobject anywhere in your application using\n\n"
                .$code2->toString()."\n"
            );
            
            echo $table->toString()."\n\n";
            
        }

        $last = microtime(true) * 1000;
        if($listen)
            $event = function( \Psr\Http\Message\ServerRequestInterface $request ) use(&$invoker,&$last,$listen) {
                $now = microtime(true) * 1000;
                if($now - $last > 100){
                    $listen();
                    $last = $now;
                }
                
                return $this->serve( $request, $invoker );
            };
        else
            $event = fn( \Psr\Http\Message\ServerRequestInterface $request ) => $this->serve( $request, $invoker );
        
        
        $server = new \React\Http\Server($loop,...[...$config->middlewares,$event]);

        $socket = new \React\Socket\Server($config->uri, $loop, $config->context);
        $server->listen($socket);
        
        $address = \preg_replace('/(tcp|unix)/','http',$socket->getAddress(),1);
        $address = \preg_replace('/tls/','https',$address);

        echo "Server running at {$address}\n";

        $loop->run();

    }

    private function serve( \Psr\Http\Message\ServerRequestInterface $request, HttpInvoker $invoker ):mixed{
        $method = $request->getMethod();
        $uri = $request->getUri();            
        $path = $uri->getPath();

        $params = [];

        //check if request matches any axposed endpoint and extract parameters
        $localPath = static::usingPath( $method,$path,$params,Meta::$FUNCTIONS );
        if(!$localPath)
            $localPath = static::usingPath( $method,$path,$params,Meta::$METHODS );

        if($localPath === null)
            return $invoker->invoke($request,$method,'@404',$params);

        try{
            $result = $invoker->invoke($request,$method,$localPath,$params);
            return $result;

        }catch(\Throwable $e){
            $message = $this->config->show_exception?$e->getMessage():'';
            $trace = $this->config->show_exception && $this->config->show_stack_trace?"\n".$e->getTraceAsString():'';
            return new \React\Http\Message\Response( 500,[],$message.$trace );

        }
            
        
    }

    const PATTERN_PARAM = '/(?<={).*(?=})/';

    private static function usingPath(string $method, string &$requestedPath, array &$params, array &$_map):?string{
        if(!isset($_map[$method])) 
            return null;
        foreach($_map[$method] as $localPath => $item){
            $localPieces = \explode('/',$localPath);
            $requestedPieces = \explode('/',$requestedPath);
            $max = \count($requestedPieces);
            $c = 0;
            foreach($localPieces as $index => &$localPiece){
                if(\preg_match(static::PATTERN_PARAM,$localPiece,$matches) && $matches && isset($matches[0])){
                    $paramName = $matches[0];
                    $paramsNames[] = $paramName;
                    $params[$paramName] = &$requestedPieces[$index];
                }else if($localPiece !== $requestedPieces[$index]){
                    return null;
                }
                $c++;
            }
            if($c === $max)
                return $localPath;
        }
        return null;
    }
}