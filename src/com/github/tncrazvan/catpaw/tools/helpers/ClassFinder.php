<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

class ClassFinder {
    //This value should be the directory that contains composer.json
    private static $appRoot = __DIR__ . "/../";

    public static function setAppRoot($root){
        static::$appRoot = $root;
    }

    public static function getClassesInNamespace($namespace):array{
        $files = scandir(static::getNamespaceDirectory($namespace));

        $classes = array_map(function($file) use ($namespace){
            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return array_filter($classes, function($possibleClass){
            return class_exists($possibleClass);
        });
    }

    private static function getDefinedNamespaces():array{
        $composerJsonPath = static::$appRoot . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        return (array) $composerConfig->autoload->{'psr-4'};
    }

    private static function getNamespaceDirectory($namespace):string|false{
        $composerNamespaces = static::getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';

            if(array_key_exists($possibleNamespace, $composerNamespaces)){
                return realpath(static::$appRoot . $composerNamespaces[$possibleNamespace] . implode('/', $undefinedNamespaceFragments));
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));            
        }

        return false;
    }
}