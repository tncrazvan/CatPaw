<?php
namespace com\github\tncrazvan\catpaw\tools\shelltools;

use com\github\tncrazvan\catpaw\tools\Dir;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\asciitable\AsciiTable;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\http\HttpController;
use com\github\tncrazvan\catpaw\websocket\WebSocketController;

class ControllerTools{
    private $actions,$info,$blueprint,$so;
    private static $singleton=null;
    private function __construct(SharedObject $so){
        $this->so=$so;
        $blueprint = ["http"=>"","websocket"=>""];
        $blueprint["http"] =
        "<?php\n"
        ."namespace #namespace;\n"
        ."use com\\github\\tncrazvan\\catpaw\\http\\HttpResponse;\n"
        ."use com\\github\\tncrazvan\\catpaw\\http\\HttpController;\n"
        ."class #classname extends HttpController{\n"
        ."  public function main(){\n"
        ."      return new HttpResponse([],\"this is #classname\");\n"
        ."  }\n"
        ."}"
        ;
        $blueprint["websocket"] =
        "<?php\n"
        ."namespace #namespace;\n"
        ."use com\\github\\tncrazvan\\catpaw\\websocket\\WebSocketController;\n"
        ."class #classname extends WebSocketController{\n"
        ."  public function onOpen(): void {\n"
        ."      echo \"Connection opened for #classname.\";\n"
        ."  }\n"
        ."  public function onMessage(string &\$data): void {\n"
        ."      echo \"Message received: \$data\\n\";\n"
        ."}\n"    
        ."  public function onClose(): void {\n"
        ."      echo \"Connection closed for #namespace/#classname.\";\n"
        ."}\n"
        ."}"
        ;
        $this->blueprint = $blueprint;
        $actions  = new AsciiTable();
        $actions->add("Action Name","Description");
        $actions->add(
            "actions",
            "[Shows] this documentation."
        );
        $actions->add(
            "new-http,\n"
            ."add-http,\n"
            ."create-http",
            "[Creates] a new http controller in your src/http directory.\n"
            ."This action won't expose the controller, that must be done manually.\n"
            ."----------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller.\n"
            ."                Example: php controller add-http my.package.MyClass"
        );
        $actions->add(
            "remove-http,\n"
            ."delete-http",  
            "[Removes] an http controller from your src/http directory.\n"
            ."This action won't un-expose the controller, that must be done manually.\n"
            ."---------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller.\n"
            ."                Example: php controller remove-http my.package.MyClass"
        );
        $actions->add(
            "new-websocket,\n"
            ."add-websocket,\n"
            ."create-websocket",
            "[Creates] a new websocket controller in your src/websocket directory.\n"
            ."This action won't expose the controller, that must be done manually.\n"
            ."--------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller.\n"
            ."                Example: php controller add-http my.package.MyClass"
        );
        $actions->add(
            "remove-websocket,\n"
            ."delete-websocket",  
            "[Removes] a websocket controller from your src/websocket directory.\n"
            ."This action won't un-expose the controller, that must be done manually.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller.\n"
            ."                Example: php controller remove-http my.package.MyClass"
        );

        $actions->add(
            "edit-http",
            "[OPENS] the http controller file using the [editor] script provided \n"
            ."in the configuration file.\n"
            ."By default the [editor] script is 'code @filename', which is Visual Studio code.\n"
            ."This actions opens both exposed and not exposed http controllers.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller and the project name.\n"
            ."                Example: php controller edit-http my.package.MyClass"
        );

        $actions->add(
            "edit-websocket",
            "[OPENS] the websocket controller file using the [editor] script \n"
            ."provided in the configuration file.\n"
            ."By default the [editor] script is 'code @filename', which is Visual Studio code.\n"
            ."This actions opens both exposed and not exposed http controllers.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller and the project name.\n"
            ."                Example: php controller edit-websocket my.package.MyClass"
        );

        $actions->add(
            "list-http",
            "[LISTS] all http controller files showing file classname, namespace\n"
            .", file name, file size, last modified date and wether or not it's exposed.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] project name.\n"
            ."                Example: php controller list-files-http"
        );

        $actions->add(
            "list-websocket",
            "[LISTS] all websocket controller files showing file classname, namespace\n"
            .", file name, file size, last modified date and wether or not it's exposed.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] project name.\n"
            ."                Example: php controller list-files-websocket"
        );

        $info = new AsciiTable();

        $info->add("What does \"full name of your controller\" mean?");
        $info->add(
            "The full name of a controller is its [namespace] followed by the [class name].\n\n"
            ."The routing separator is \".\" instead of \"\\\".\n\n"
            ."For example: [creating] a controller named \"FileManager\" under the\n"
            ."namespace \"filesystem\\manager\" would be done executing:\n"
            ."\"php controller add filesystem.manager.FileManager\"."
        );
        $this->actions = $actions;
        $this->info = $info;
    }


    
    public static function get(SharedObject $so):ControllerTools{
        if(self::$singleton === null)
        self::$singleton = new ControllerTools($so);
        return self::$singleton;
    }
    public function getDocumentationActions():string{
        return $this->actions->toString();
    }
    public function getDocumentationInfo():string{
        return $this->info->toString();
    }

    private function getBlueprint(string $type,array &$metadata):string{
        $blueprint = $this->blueprint[$type];
        $blueprint = preg_replace('/\#namespace/',$metadata["namespace"],$blueprint);
        $blueprint = preg_replace('/\#classname/',$metadata["classname"],$blueprint);
        return $blueprint;
    }

    private function create(string $type,string $arg1,array &$metadata, bool $force = false):void{
        if(!$force && \file_exists($metadata["filename"])){
            echo "[FAILURE] File {$metadata['filename']} already exists. Will not overwrite.\n";
            return;
        }
        if(!file_put_contents($metadata["filename"],$this->getBlueprint($type,$metadata))){
            echo "[FAILURE] Controller [{$metadata['namespace']}\\{$metadata['classname']}] could not be created.\n";
            echo "[FAILURE] Error when attempting to write the file [{$metadata['filename']}].\n";
            return;
        }
        shell_exec("composer dump-autoload -o");
        echo "[SUCCESS] Controller [{$metadata['namespace']}\\{$metadata['classname']}] created.\n";
        if($this->so->scripts["editor"] !== ""){
            $editor = preg_replace('/@filename/',$metadata["filename"],$this->so->scripts["editor"]);
            shell_exec($editor);
        }
    }

    private function delete(array &$metadata):void{
        if(!\file_exists($metadata["filename"])){
            echo "[FAILURE] File {$metadata['filename']} not found.\n";
            return;
        }
        if(unlink($metadata["filename"])){
            shell_exec("composer dump-autoload -o");
            echo "[SUCCESS] Controller [{$metadata['namespace']}\\{$metadata['classname']}] removed.\n";
        }else{
            echo "[FAILURE] Controller [{$metadata['namespace']}\\{$metadata['classname']}] could not be removed.\n";
        }
    }

    private function edit(string $type,array &$metadata):void{
        if(!\file_exists($metadata["filename"])){
            echo "[FAILURE] File {$metadata['filename']} doesn't exist. Try creating the controller using 'php controller add-$type <controller name>@<project name>'.\n";
            return;
        }
        if($this->so->scripts["editor"] !== ""){
            $editor = preg_replace('/@filename/',$metadata["filename"],$this->so->scripts["editor"]);
            shell_exec($editor);
        }else{
            echo "[FAILURE] Editor script is not set.\n";
        }
    }

    private function config():void{
        if(!\file_exists("config/config.php")){
            echo "[FAILURE] File 'config/config.php' doesn't exist.\n";
            return;
        }
        if($this->so->scripts["editor"] !== ""){
            $editor = preg_replace('/@filename/','config/config.php',$this->so->scripts["editor"]);
            shell_exec($editor);
        }else{
            echo "[FAILURE] Editor script is not set.\n";
        }
    }
    private function list(string $type):void{
        $dir = [];
        $table = new AsciiTable();
        $table->add("Path","Controller Name","File Name","Size","Last Modified","Exposed");
        Dir::getFilenamesRecursive("src/$type",$dir);
        foreach($dir as &$file){
            $path = "";
            $exposed = "NO";
            $namespace = "[unknown]";
            $classname = "[unknown]";
            $contents = file_get_contents($file["name"]);
            $rows = preg_split('/\n/',$contents);
            $namespaceGroups = preg_grep('/^\s*namespace \s*.*$/',$rows);
            if(count($namespaceGroups) > 0){
                $namespace = trim(preg_replace('/^\s*namespace \s*/','',$namespaceGroups[array_key_first($namespaceGroups)]));
                $namespace = preg_replace('/\s*;\s*$/','',$namespace);
            }
            $classnameGroups = preg_grep('/(?<=class)\s+[A-z][A-z0-9_]+\s+/',$rows);
            
            if(count($classnameGroups) > 0){
                $classname = trim($classnameGroups[array_key_first($classnameGroups)]);
                if(preg_match('/(?<=class)\s+[A-z][A-z0-9_]+\s+/',$classname,$out)){
                    $classname = trim($out[array_key_first($out)]);
                }
            }
            foreach($this->so->controllers[$type] as $myPath => &$cls){
                if($cls === $namespace."\\".$classname){
                    $exposed = "YES";
                    $path = $myPath;
                }
            }
            //app\mysubapp
            $thisNamespaceEscaped = preg_replace('/\\\+/','\\.',$this->so->namespace);
            //app.mysubapp

            $namespace = preg_replace('/\\\+/','.',$namespace);

            //app.mysubapp.http
            $convertedNamespace = preg_replace("/^$thisNamespaceEscaped/",'',$namespace);
            //http


            $convertedNamespace = preg_replace("/^\\.*$type\\.*/",'',$convertedNamespace);
            $classname = preg_replace('/\\\+/','.',$classname);
            $classname = ($convertedNamespace !== ""?$convertedNamespace.'.':"").$classname;
            $table->add($path,$classname,$file["name"],$file["size"],$file["lastChange"],$exposed);
        }
        echo "{$table->toString()}\n";
    }

    private function assertArgument(string $arg1):bool{
        if($arg1 === ""){
            echo "This action requires arguments, please run \"controller actions\" for more information.\n";
            return false;
        }
        return true;
    }

    public function &action(string $action,string $arg1):array{
        $arg1 = trim($arg1);
        $metadata = [];
        switch($action){
            case "config":
                $this->config();
            break;
            case "list-http":
                $this->list("http");
            break;
            case "list-websocket":
                $this->list("websocket");
            break;
            case "edit-http":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("http",$arg1,true);
                $this->edit("http",$metadata);
            break;
            case "edit-websocket":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("websocket",$arg1,true);
                $this->edit("websocket",$metadata);
            break;
            case "remove-http":
            case "delete-http":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("http",$arg1,true);
                $this->delete($metadata);
            break;
            case "remove-websocket":
            case "delete-websocket":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("websocket",$arg1,true);
                $this->delete($metadata);
            break;
            case "new-http":
            case "add-http":
            case "create-http":
            case "new-http-force":
            case "add-http-force":
            case "create-http-force":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("http",$arg1,true);
                $this->create("http",$arg1,$metadata,Strings::endsWith($action,"-force"));
            break;
            case "new-websocket":
            case "add-websocket":
            case "create-websocket":
            case "new-websocket-force":
            case "add-websocket-force":
            case "create-websocket-force":
                if(!$this->assertArgument($arg1)) return $metadata;
                $metadata = &$this->resolveControllerName("websocket",$arg1,true);
                $this->create("websocket",$arg1,$metadata,Strings::endsWith($action,"-force"));
            break;
            case "actions":
                $actions = $this->getDocumentationActions();
                $info = $this->getDocumentationInfo();
                echo "$actions\n";
                echo "$info\n";
            break;
            default: 
                echo "Invalid action ($action).\n";
                $actions = $this->getDocumentationActions();
                $info = $this->getDocumentationInfo();
                echo "$actions\n";
                echo "$info\n";
            break;
        }
        return $metadata;
    }

    private function &resolveControllerName(string $type,string $arg1, bool $subdirs):array{
        if($subdirs){
            if(!\file_exists("src"))
                mkdir("src");
            if(!\file_exists("src/$type"))
                mkdir("src/$type");
        }
        $metadata = [
            "namespace"=>"",
            "classname"=>"",
            "filename" =>""
        ];
        $classname = "";
        $directory = "";

        $pieces = preg_split('/\./',$arg1);
        for($i = 0,$len = count($pieces); $i < $len; $i++){
            if($i === $len - 1){
                $classname = $pieces[$i];
                continue;
            }
            $directory .= "/".$pieces[$i];
            if($subdirs && !file_exists("src/$type$directory")){
                mkdir("src/$type$directory");
            }
        }
        
        $namespace = $this->so->namespace.preg_replace('/\//',"\\",'\\'.$type.$directory);

        $filename = "src/$type$directory/$classname.php";
        $metadata["xec"] = "NEW CONTROLLER";
        $metadata["namespace"] = $namespace;
        $metadata["classname"] = $classname;
        $metadata["filename"] = $filename;
        return $metadata;
    }
}