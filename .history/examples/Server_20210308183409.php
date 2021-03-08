<?php
namespace examples;

use net\razshare\catpaw\attributes\Entry;
use net\razshare\catpaw\attributes\Filter;
use net\razshare\catpaw\attributes\FilterItem;
use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Produces;
use net\razshare\catpaw\attributes\sessions\Session;
use net\razshare\catpaw\attributes\Singleton;
use net\razshare\catpaw\tools\Status;
use React\Promise\Promise;

#[FilterItem]                                   //#[FilterItem] is an alis for #[Singleton] atm.
                                                //More options to come in the future.
class TestFilter{

    #[Entry]                                    //#[Entry] specifies that this is the entry point of this filter item.
    #[Produces("text/html")]                    //Specifies the produced content-type.
                                                //All normal web attributes can be used here except for Path and method names such as [GET],[POST] etc..
    public function test(
        #[Session] array &$session,             //Starts a session or grabs an existing one.
        #[Status] Status $status                //Specifies the response status.
                                                //All web attributes are available here including injections.
    ):mixed{
        $username = $session["username"]??null;
        if(!$username){
            $status->setCode(Status::UNAUTHORIZED);
            $session["username"] = "tncrazvan";
            return "Please login.";                     //if this function returns anything else than <null>, the request is blocked and the returned value is served.
        }
        return null;                                    //if this method returns null then it means the request went through the filter
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
    #[Filter(TestFilter::class,TestFilter2::class)]     //Adds the 2 filters TestFilter and TestFilter2.
                                                        //this method will be executed only if the request passes through both filters.
                                                        //Filter executions are ordered, so TestFilter will be executed first
                                                        //then TestFilter2 will be executed, but only if TestFilter lets the request through.
    public function test():array{
        return [
            "username" => "jotey",
            "message" => "hello from jotey"
        ];
    }
}