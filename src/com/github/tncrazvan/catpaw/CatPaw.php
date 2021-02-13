<?php
namespace com\github\tncrazvan\catpaw;

use com\github\tncrazvan\catpaw\attributes\helpers\metadata\Meta;
use com\github\tncrazvan\catpaw\sessions\SessionManager;

class CatPaw{
    private array $_map = [];
    public function __construct(
        private int $port = 8080,
        private array $events = [],
        private ?\React\EventLoop\LoopInterface $loop = null,
        private ?\React\Http\Server $server = null
    ){
        $sm = new SessionManager();
        $invoker = new HttpInvoker($sm);

        foreach ( $this->events as $key => $event ) {
            if( $event instanceof HttpEvent ){
                if( !isset( $this->_map[$event->getMethod()] ) ){
                    $this->_map[$event->getMethod()][$event->getUri()] = $event->getClosure();
                }
            }
        }

        $loop = \React\EventLoop\Factory::create();
        $server = new \React\Http\Server( 
            $loop, 
            fn( \Psr\Http\Message\ServerRequestInterface $request ) => $this->serve( $request, $invoker )
        );
        

        $socket = new \React\Socket\Server(8080, $loop);
        $server->listen($socket);
        
        echo "Server running at http://127.0.0.1:$port\n";

        $loop->run();

    }

    private function serve( \Psr\Http\Message\ServerRequestInterface $request, HttpInvoker $invoker ):\React\Http\Message\Response{
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

            return new \React\Http\Message\Response( 500,[],"{$e->getMessage()}\n{$e->getTraceAsString()}\n" );

        }
            
        
    }

    const PATTERN_PARAM = '/(?<={).*(?=})/';

    private static function usingPath(string $method, string &$requestedPath, array &$params, array &$_map):?string{
        if(!isset($_map[$method])) 
            return null;
        foreach($_map[$method] as $localPath => $item){
            $localPieces = \explode('/',$localPath);
            $max = \count($localPieces);
            $c = 0;
            $requestedPieces = \explode('/',$requestedPath);
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