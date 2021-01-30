<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

class ClassFinder {
    //This value should be the directory that contains composer.json
    private $appRoot = __DIR__ . "/../";

    public function setAppRoot($root){
        $this->appRoot = $root;
    }

    public function getClassesInNamespace(string $namespace, \Closure $onSubDir=null):array{
        $dir = $this->getNamespaceDirectory($namespace);
        $files = scandir($dir);
        $classes = array_map(function($file) use (&$namespace,&$onSubDir,&$dir){
            if($onSubDir !== null && $file !== '.' && $file !== '..' && is_dir($dir.'/'.$file))
                $onSubDir($file);
            

            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return array_filter($classes, function(string $possibleClass){
            return class_exists($possibleClass);
        });
    }

    private function getDefinedNamespaces():array{
        $composerJsonPath = $this->appRoot . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        return (array) $composerConfig->autoload->{'psr-4'};
    }

    private function getNamespaceDirectory(string $namespace):string|false{
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';

            if(array_key_exists($possibleNamespace, $composerNamespaces)){
                return realpath($this->appRoot . $composerNamespaces[$possibleNamespace] . implode('/', $undefinedNamespaceFragments));
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));            
        }

        return false;
    }
}