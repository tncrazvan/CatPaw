<?php
namespace examples\repositories;

use com\github\tncrazvan\catpaw\attributes\Repository;
use com\github\tncrazvan\catpaw\qb\operations\Operation;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\tools\helpers\CrudRepository;
use examples\models\Task;

#[Repository(Task::class)]          //specify that this is a repository for the `Todo` model.
                                    //this way the repository knows whic tables to query and what
                                    //the collumns are.
                                    //also this class is now injectable as a singleton.
class TaskRepository 
    extends CrudRepository          //this will provide some basic crud operations
                                    //and the simple query builder we instantiated as a singleton
                                    //in the beginning in `main.php`
    {
        public function update(Task $task):void{
            $this
                ->builder
                ->update(Task::class,$task)
                ->where()
                ->column('id',Column::EQUALS,$task->id)
                ->execute()
                ;
        }

        //nothing else to do here, this repository already has the basic
        //functionalities of CRUD because of `extends CrudRepository`.
}