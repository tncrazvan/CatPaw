<?php
namespace examples\filters;

use net\razshare\catpaw\attributes\Body;
use net\razshare\catpaw\attributes\Consumes;
use net\razshare\catpaw\attributes\Entry;
use net\razshare\catpaw\attributes\FilterItem;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\tools\Status;
use examples\models\Task;
use examples\repositories\TaskRepository;

#[FilterItem]
class AssertTaskExists{

    #[Entry]
    #[Consumes("application/json")]
    public function check(
        #[Body] Task $task,
        #[Status] Status $status,
        #[Inject] TaskRepository $repo
    ){
        $dbtask = $repo->findById($task->id);
        if(!$dbtask){
            $status->setCode(Status::PRECONDITION_FAILED);
            return "Task {$task->id} does not exist.";
        }

        return null;    //task passed through the filter
    }

}