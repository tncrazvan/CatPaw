<?php

use com\github\tncrazvan\catpaw\attributes\database\Column;
use com\github\tncrazvan\catpaw\attributes\database\Id;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Repository;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\helpers\Entity;
use com\github\tncrazvan\catpaw\tools\helpers\CrudRepository;

#[Entity]
class Article {
    #[Id] public int $id;
    #[Column] public string $title;
    #[Column] public string $content;
}

#[Repository(Article::class)]
class ArticleRepository extends CrudRepository{}

#[Singleton]
class App{


    #[Entry]
    public function main(
        #[Inject] ArticleRepository $repo
    ):void{

        $repo->findAll()->then(function($result){
            print_r($result);
        });
        echo "hello world!!!!\n";

    }
}