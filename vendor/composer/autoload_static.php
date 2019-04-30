<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9c25ca0edfdb51b728fbf5957c11ee11
{
    public static $fallbackDirsPsr4 = array (
        0 => __DIR__ . '/../..' . '/src',
        1 => 'C:\\Users\\Razvan\\Documents\\NetBeansProjects\\CatServer\\vendor',
    );

    public static $classMap = array (
        'App' => __DIR__ . '/../..' . '/src/php/controllers/App.php',
        'ComposerAutoloaderInit9c25ca0edfdb51b728fbf5957c11ee11' => __DIR__ . '/..' . '/composer/autoload_real.php',
        'Composer\\Autoload\\ClassLoader' => __DIR__ . '/..' . '/composer/ClassLoader.php',
        'Composer\\Autoload\\ComposerStaticInit9c25ca0edfdb51b728fbf5957c11ee11' => __DIR__ . '/..' . '/composer/autoload_static.php',
        'ControllerNotFound' => __DIR__ . '/../..' . '/src/php/controllers/ControllerNotFound.php',
        'HelloWorld' => __DIR__ . '/../..' . '/src/php/controllers/HelloWorld.php',
        'com\\github\\tncrazvan\\CatServer\\Cat' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Cat.php',
        'com\\github\\tncrazvan\\CatServer\\CatServer' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/CatServer.php',
        'com\\github\\tncrazvan\\CatServer\\Controller\\Http\\App' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Controller/Http/App.php',
        'com\\github\\tncrazvan\\CatServer\\Controller\\Http\\ControllerNotFound' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Controller/Http/ControllerNotFound.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\EventManager' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/EventManager.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpController' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpController.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpEvent' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpEvent.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpEventListener' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpEventListener.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpEventManager' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpEventManager.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpHeader' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpHeader.php',
        'com\\github\\tncrazvan\\CatServer\\Http\\HttpRequestReader' => __DIR__ . '/..' . '/com/github/tncrazvan/CatServer/Http/HttpRequestReader.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->fallbackDirsPsr4 = ComposerStaticInit9c25ca0edfdb51b728fbf5957c11ee11::$fallbackDirsPsr4;
            $loader->classMap = ComposerStaticInit9c25ca0edfdb51b728fbf5957c11ee11::$classMap;

        }, null, ClassLoader::class);
    }
}
