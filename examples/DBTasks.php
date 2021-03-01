<?php
namespace examples;

use com\github\tncrazvan\catpaw\attributes\Body;
use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\Filter;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\methods\POST;
use com\github\tncrazvan\catpaw\attributes\http\methods\PUT;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\PathParam;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\qb\tools\Page;
use com\github\tncrazvan\catpaw\tools\Status;
use examples\filters\AssertTaskExists;
use examples\filters\CheckTaskTitleLength;
use examples\models\Task;
use examples\repositories\TaskRepository;
use Generator;


#[Path("/tasks")]                           //specify the http base path.
                                            //all web methods within this class will 
                                            // prepend "/todo" to their path.
                                            //you can also specify path parameters in this base path,
                                            //which will be inherited by the web method paths below.
class DBTasks{

    #[GET]                                  //specify the http method
    #[Produces("application/json")]         //specify what type of content this method produces
                                            //by default this is "text/plain".
                                            //note that if this does not match with the "Accept" header
                                            //of the request the request will fail.
                                            //you can specify multiple types like so:
                                            //#[Produces("application/json,text/plain,application/xml")]
    public function findAllTasks(
        #[Inject] TaskRepository $repo      //inject the repository
    ):Generator|array{                      //specify it's a generator so that intellisense won't scream at you
        
        $tasks = yield $repo->findAll();    //find the todos from the database and `yield`.
                                            //`yield`ing will make it so the server will convert the 
                                            //response of this method to a `Promise` in the background.
                                            //This means that from this point on your method has become
                                            //an asynchronous method, everytime you `yield` execution will be paused
                                            //and resumed on the next loop tick.
                                            //Note that you don't have to provide an instance of the loop object,
                                            //that's because loop itself is a singleton which the server can recover easily.

        return $tasks;                      //return the todos.
    }

    #[POST]
    #[Consumes("application/json")]         //specify it consumes json data.
                                            //this means user MUST send an "Content-Type: application/json" header.
                                            //in the request.
    
    #[Filter(CheckTaskTitleLength::class)]       //filter the request.
                                            //this filter will mae sure that the task, for example, has a title
                                            //long at least 10 characters.
    public function addNewTask(
        #[Inject] TaskRepository $repo,
        #[Status] Status $status,
        #[Body] Task $task                  //cast the data into a `Task` object.
    ):string{
        $task->id = null;                   //make sure the `id` is null so that mysql
                                            //will provide this value instead.
        $repo->insert($task);
        $status->setCode(Status::CREATED);
        return "Task added.";
    }

    ###################################################################
    #                                                                 #
    #  much of what is going on in this PUT endpoint is the same as   #
    #  what is going on in the above POST endpoint.                   #
    #                                                                 #
    ###################################################################
    #[PUT]
    #[Consumes("application/json")]
    #[Filter(
        CheckTaskTitleLength::class,    //make sure the new task title is at least
                                        //10 characters long

        AssertTaskExists::class         //make sure the task exists before even trying
                                        //to update it
    )]
    public function updateTask(
        #[Inject] TaskRepository $repo,
        #[Body] Task $task
    ):Generator|string{
        $task->updated = time();
        $repo->update($task);           //update task
        return "Task updated.";
    }



    #[GET]
    #[Path("/{id}")]
    #[Produces("application/json")]
    public function findById(
        #[Inject] TaskRepository $repo,
        #[PathParam] int $id
    ):Generator|Task{
        $task = yield $repo->findById($id);
        return $task;
    }

    #[GET]
    #[Path("/page/{offset}")]
    #[Produces("application/json")]
    public function findTasksPage(
        #[Inject] TaskRepository $repo,
        #[PathParam] int $offset
    ):Generator|array{
        $tasks = yield $repo->findAll(Page::of($offset,3));
        return $tasks;
    }
}