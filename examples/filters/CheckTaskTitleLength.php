<?php
namespace examples\filters;

use net\razshare\catpaw\attributes\Body;
use net\razshare\catpaw\attributes\Consumes;
use net\razshare\catpaw\attributes\Entry;
use net\razshare\catpaw\attributes\FilterItem;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\tools\Status;
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