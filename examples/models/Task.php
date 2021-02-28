<?php
namespace examples\models;

use com\github\tncrazvan\catpaw\attributes\database\Column;
use com\github\tncrazvan\catpaw\attributes\database\Id;
use com\github\tncrazvan\catpaw\attributes\database\IgnoreUpdate;
use com\github\tncrazvan\catpaw\tools\helpers\Entity;
//use PDO;

#[Entity]
class Task{
    #[Id] public ?int $id = null;                   //null by default, so that mysql autoincrements is
                                                    //you can also specify the type  manually 
                                                    //by using #[ID(PDO::PARAM_**)]
    #[Column] public string $title = '';
    #[Column] public string $description = '';

    #[IgnoreUpdate]                                 //this will make it so that the `created` column
                                                    //will not be set when `update`ing the entity.
    #[Column] public int $created;
    #[Column] public int $updated;

    public function __construct(){
        $this->created = time();
        $this->updated = $this->created;
    }
}