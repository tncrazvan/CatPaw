<?php

use com\github\tncrazvan\catpaw\attributes\database\Column;
use com\github\tncrazvan\catpaw\attributes\database\Id;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Repository;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\helpers\Entity;
use com\github\tncrazvan\catpaw\tools\helpers\CrudRepository;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;

Factory::setConstructorInjector(SimpleQueryBuilder::class,fn()=>[new PDO("mysql:dbname=razshare;host=127.0.0.1","razshare","razshare")]);

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
        
        print_r($repo->findAll());
    }
}