<?php
namespace app;

use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Filter;
use com\github\tncrazvan\catpaw\attributes\FilterItem;
use com\github\tncrazvan\catpaw\attributes\http\Headers;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\attributes\sessions\Session;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\Status;

#[FilterItem]
class TestFilter{

    #[Entry]
    #[Produces("text/html")]
    public function test(
        #[Session] array &$session,
        #[Status] Status $status
    ):mixed{
        $username = $session["username"]??null;
        if(!$username){
            $status->setCode(Status::UNAUTHORIZED);
            $session["username"] = "tncrazvan";
            return "Please login.";
        }
        return null;
    }
}

#[FilterItem]
class TestFilter2{

    #[Entry]
    #[Produces("text/html")]
    public function test(
        #[Session] array &$session,
        #[Status] Status $status
    ):mixed{
        $username = $session["username"];
        if($username !== "jotey"){
            $status->setCode(Status::UNAUTHORIZED);
            $session["username"] = "jotey";
            return "User $username does not have the required permissions to obtain this resource.";
        }
        return null;
    }
}

#[Path("/")]
class Server{

    #[GET]
    #[Produces("application/json")]
    #[Filter(TestFilter::class,TestFilter2::class)]
    public function test():array{
        return [
            "username" => "jotey",
            "message" => "hello from jotey"
        ];
    }
}