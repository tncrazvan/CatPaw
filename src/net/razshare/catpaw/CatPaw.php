<?php
namespace net\razshare\catpaw;

use net\razshare\asciitable\AsciiTable;
use net\razshare\catpaw\attributes\metadata\Meta;
use net\razshare\catpaw\attributes\Singleton;
use net\razshare\catpaw\config\MainConfiguration;
use net\razshare\catpaw\sessions\SessionManager;
use net\razshare\catpaw\tools\helpers\Factory;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\LoopInterface;

class CatPaw{
    private array $_map = [];
    private \React\Socket\Server $socket;
    public function __construct(
        private MainConfiguration $config,
        private \React\EventLoop\LoopInterface $loop,
        private array $events = []
    ){
        $sm = new SessionManager($config);

        foreach ( $this->events as $key => $event ) {
            if( $event instanceof HttpEvent ){
                if( !isset( $this->_map[$event->getMethod()] ) ){
                    $this->_map[$event->getMethod()][$event->getUri()] = $event->getClosure();
                }
            }
        }

        if(!Factory::isset(LoopInterface::class)){
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
        
        $invoker = new HttpInvoker($this->loop,$sm);

        $event = fn( \Psr\Http\Message\ServerRequestInterface $request ) => $this->serve( $request, $invoker );
        
        
        $server = new \React\Http\Server($this->loop,...[...$config->middlewares,$event]);

        $this->socket = new \React\Socket\Server($config->uri, $this->loop, $config->context);
        $server->listen($this->socket);

        $address = \preg_replace('/(tcp|unix)/','http',$this->socket->getAddress(),1);
        $address = \preg_replace('/tls/','https',$address);

        echo "Server running at {$address}\n";

        $this->loop->run();
    }

    private function serve( \Psr\Http\Message\ServerRequestInterface $request, HttpInvoker $invoker ):mixed{
        $method = $request->getMethod();
        $uri = $request->getUri();            
        $path = $uri->getPath();

        $params = [];

        //check if request matches any axposed endpoint and extract parameters
        $local_path = static::usingPath( $method,$path,$params,Meta::$FUNCTIONS );
        if(!$local_path)
            $local_path = static::usingPath( $method,$path,$params,Meta::$METHODS );

        if($local_path === null)
            return $invoker->invoke($request,$method,'@404',$params);

        try{
            $result = $invoker->invoke($request,$method,$local_path,$params);
            return $result;

        }catch(\Throwable $e){
            $message = $this->config->show_exception?$e->getMessage():'';
            $trace = $this->config->show_exception && $this->config->show_stack_trace?"\n".$e->getTraceAsString():'';
            return new \React\Http\Message\Response( 500,[],$message.$trace );
        }
            
        
    }
    
    private static function usingPath(string $method, string &$requested_path, array &$params, array &$_map):?string{
        if(!isset($_map[$method])) 
            return null;
        foreach($_map[$method] as $local_path => $item){
            $path_patterns = Meta::$HTTP_METHODS_PATHS_PATTERNS[$method][$local_path];
            foreach($path_patterns as $path_pattern){
                $matching = \preg_match($path_pattern,$requested_path,$values);
                if($matching){
                    if(\preg_match_all('/(?<={)[\w\d\-_\.\~]+(?=})/',$local_path,$names)){
                        $l = \count($values);
                        for($i=1;$i<$l;$i++){
                            $params[$names[0][$i-1]] = \urldecode($values[$i]);
                        }
                    }
                    return $local_path;
                }
            }
        }
        return null;
    }
}