<?php
require_once './vendor/autoload.php';

use net\razshare\catpaw\CatPaw;
use net\razshare\catpaw\config\MainConfiguration;
use net\razshare\catpaw\tools\helpers\Factory;
use net\razshare\catpaw\tools\helpers\SimpleQueryBuilder;
use React\EventLoop\LoopInterface;

//make a new loop and make it injectable
Factory::setObject(LoopInterface::class,\React\EventLoop\Factory::create());

//make a new simple query builder using a PDO connection and make it injectable
Factory::setObject(
    SimpleQueryBuilder::class,new SimpleQueryBuilder(
        new \PDO(                                       //make the pdo object
            "mysql:"                                    //dsn
            ."host=localhost;"
            ."dbname=todos;"
            ."port=3306;"
            ."charset=utf8mb4;",
            "razshare",                                 //username
            "razshare"                                  //password
        ),
        Factory::make(LoopInterface::class)             //Inject the loop that has just been created above
    )
);

//scan classes
//using `cat-template`, this will be done automatically for you.
Factory::make(\examples\Server::class);
Factory::make(\examples\YieldTest::class);
Factory::make(\examples\Starter::class);
Factory::make(\examples\repositories\TaskRepository::class);
Factory::make(\examples\models\Task::class);
Factory::make(\examples\DBTasks::class);
Factory::make(\examples\ProcessTest::class);
Factory::make(\examples\ByteRangeTest::class);

//create and start server
$server = new CatPaw(new class extends MainConfiguration{
    public function __construct() {
        $this->show_exception = true;
        $this->show_stack_trace = false;
    }
},Factory::make(LoopInterface::class));