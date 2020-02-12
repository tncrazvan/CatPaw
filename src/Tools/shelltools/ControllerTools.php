<?php
namespace com\github\tncrazvan\catpaw\tools\shelltools;

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
            ."----------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller."
        );
        $actions->add(
            "remove-http,\n"
            ."delete-http",  
            "[Removes] an http controller from your src/http directory.\n"
            ."---------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller."
        );
        $actions->add(
            "new-websocket,\n"
            ."add-websocket,\n"
            ."create-websocket",
            "[Creates] a new websocket controller in your src/websocket directory.\n"
            ."--------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller."
        );
        $actions->add(
            "remove-websocket,\n"
            ."delete-websocket",  
            "[Removes] a websocket controller from your src/websocket directory.\n"
            ."------------------------------------------------------------------\n"
            ."Arguments:\n"
            ."         |\n"
            ."         +--[1] full name of your controller."
        );

        $info = new AsciiTable();

        $info->add("What does \"full name of your controller\" mean?");
        $info->add(
            "The full name of a controller is the [namespace] the class that identifies\n"
            ."the controller followed by the [class name] itself.\n\n"
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

    private function create(string $type,string $arg1,array &$metadata, bool $force = false){
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
        if($this->so->editor !== ""){
            $editor = preg_replace('/@filename/',$metadata["filename"],$this->so->editor);
            shell_exec($editor);
        }
    }

    private function delete(array &$metadata){
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

    private function assertArgument(string $arg1,string $message):bool{
        if($arg1 === ""){
            echo $message;
            return false;
        }
        return true;
    }

    public function &action(string $action,string $arg1):array{
        $arg1 = trim($arg1);
        $metadata = [];
        if(!$this->assertArgument($arg1,"This action requires arguments, please run \"controller actions\" for more information.\n")) return $metadata;
        switch($action){
            case "remove-http":
            case "delete-http":
                $metadata = &$this->resolveControllerName("http",$arg1,true);
                $this->delete($metadata);
            break;
            case "remove-websocket":
            case "delete-websocket":
                $metadata = &$this->resolveControllerName("websocket",$arg1,true);
                $this->delete($metadata);
            break;
            case "new-http":
            case "add-http":
            case "create-http":
            case "new-http-force":
            case "add-http-force":
            case "create-http-force":
                $metadata = &$this->resolveControllerName("http",$arg1,true);
                $this->create("http",$arg1,$metadata,Strings::endsWith($action,"-force"));
            break;
            case "new-websocket":
            case "add-websocket":
            case "create-websocket":
            case "new-websocket-force":
            case "add-websocket-force":
            case "create-websocket-force":
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
        $namespace = $this->so->httpControllerPackageName.preg_replace('/\//',"\\",$directory);
        $filename = "src/$type$directory/$classname.php";
        $metadata["xec"] = "NEW CONTROLLER";
        $metadata["namespace"] = $namespace;
        $metadata["classname"] = $classname;
        $metadata["filename"] = $filename;
        return $metadata;
    }
}