<?php
namespace com\github\tncrazvan\catpaw\attributes\templates;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\services\TwigService;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;

#[\Attribute]
class Twig implements AttributeInterface{
    use CoreAttributeDefinition;
    private TwigService $twig;
    private array $context = [];
    public function __construct(
        private string $fileName,
        private ?string $template = null
    ){
        $this->twig = Factory::make(TwigService::class);
        $config = Factory::make(MainConfiguration::class);
        if($config instanceof MainConfiguration){
            if($template === null){
                $template = file_get_contents($fileName);
            }
            $this->twig->getArrayLoader()->setTemplate($fileName,$template);   
        }
    }

    public function setContext(array $context):Twig{
        $this->context = $context;
        return $this;
    }

    public function set(string $key, mixed $value):Twig{
        $this->context[$key] = $value;
        return $this;
    }

    public function render():string{
        return $this->twig->getEnvironment()->render($this->fileName,$this->context);
    }
}