<?php
namespace com\github\tncrazvan\catpaw\services;
use com\github\tncrazvan\catpaw\attributes\Service;

#[Service]
class TwigService{
    private \Twig\Loader\ArrayLoader $loader;
    private \Twig\Environment $twig;
    public function __construct(){
        $this->loader = new \Twig\Loader\ArrayLoader();
        $this->twig = new \Twig\Environment($this->loader);
    }

    public function getArrayLoader():\Twig\Loader\ArrayLoader{
        return $this->loader;
    }
    public function getEnvironment():\Twig\Environment{
        return $this->twig;
    }
}