<?php
namespace com\github\tncrazvan\CatPaw\Tools;

abstract class G{
    const DIR = __DIR__;
    public static
            $sessionDir = "",
            $sessionName = "/SESSION",
            $certificateName = "",
            $certificateType = "",
            $certificatePrivateKey = "",
            $certificatePassphrase = "",
            $ramSession = [
                "allow"=>true,
                "size"=>1024 // 1024 MB
            ],
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
            
            $httpControllerPackageNameOriginal="com\\github\\tncrazvan\\CatPaw\\Controller\\Http",
            $httpControllerPackageName="com\\github\\tncrazvan\\CatPaw\\Controller\\Http",
            
            $wsControllerPackageNameOriginal="com\\github\\tncrazvan\\CatPaw\\Controller\\WebSocket",
            $wsControllerPackageName="com\\github\\tncrazvan\\CatPaw\\Controller\\WebSocket",
            
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
}