<?php
namespace examples\filters;

use com\github\tncrazvan\catpaw\attributes\Body;
use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\FilterItem;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\Status;
use examples\models\Task;

#[FilterItem]
class CheckTaskTitleLength{

    #[Entry]
    #[Consumes("application/json")]
    public function check(
        #[Body] Task $task,
        #[Status] Status $status
    ){
        if(strlen($task->title) < 10){
            $status->setCode(Status::PRECONDITION_FAILED);
            return "Task title must be at least 10 characters long.";   //task was blocked by the filter
        }

        return null;    //task passed through the filter
    }

}