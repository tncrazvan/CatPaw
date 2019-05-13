<?php
namespace com\github\com\tncrazvan\CatServer\Tools;

abstract class G{
    const DIR = __DIR__;
    public static
            $certificateName = "",
            $certificateType = "",
            $certificatePrivateKey = "",
            $certificatePassword = "",
            $sessionSize = 1024, // 1024 MB
            $sessionTtl = 1440, // 24 minutes
            $sleep = 10, //microseconds
            $listen=true,
            $groupsAllowed=false,
            $smtpAllowed=false,
            $backlog=50,
            $port=80,
            $timeout=3000,
            $webRoot="/src/",
            $charset="UTF-8",
            $bindAddress="127.0.0.1",
            
            $httpControllerPackageNameOriginal="com\\github\\tncrazvan\\CatServer\\Controller\\Http",
            $httpControllerPackageName="com\\github\\tncrazvan\\CatServer\\Controller\\Http",
            
            $wsControllerPackageNameOriginal="com\\github\\tncrazvan\\CatServer\\Controller\\WebSocket",
            $wsControllerPackageName="com\\github\\tncrazvan\\CatServer\\Controller\\WebSocket",
            
            $httpNotFoundNameOriginal="ControllerNotFound",
            $httpNotFoundName="ControllerNotFound",
            
            $wsNotFoundNameOriginal="ControllerNotFound",
            $wsNotFoundName="ControllerNotFound",
            
            $httpDefaultNameOriginal="App",
            $httpDefaultName="App",

            $wsEvents,
            $cookieTtl=60*60*24*365, //year
            $wsGroupMaxClient=10,
            $wsMtu=65536,
            $httpMtu=65536,
            $cacheMaxAge=60*60*24*365, //year
            $entryPoint="/index.html",
            $wsAcceptKey = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11",
            $mainSettings,
            $running=true;
    
    const DATE_FORMAT = "D j M Y G:i:s T";
    const PATTERN_JS_ESCAPE_LEFT_START = "<\\s*(?=script)";
    const PATTERN_JS_ESCAPE_LEFT_END = "<\\s*\\/\\s*(?=script)";
    const PATTERN_JS_ESCAPE_RIGHT_START1 = "(?<=\\&lt\\;script)\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_START2 = "(?<=\\&lt\\;script).*\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_END = "(?<=&lt;\\/script)>";
}