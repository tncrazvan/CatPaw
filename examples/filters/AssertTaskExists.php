<?php
namespace examples\filters;

use com\github\tncrazvan\catpaw\attributes\Body;
use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\FilterItem;
use com\github\tncrazvan\catpaw\attributes\http\RequestHeaders;
use com\github\tncrazvan\catpaw\attributes\http\ResponseHeaders;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\Status;
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