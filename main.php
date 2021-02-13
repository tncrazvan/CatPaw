<?php
require_once './vendor/autoload.php';

use com\github\tncrazvan\catpaw\attributes\Body;
use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\helpers\Factory;
use com\github\tncrazvan\catpaw\attributes\http\Headers;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\methods\POST;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\PathParam;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\attributes\sessions\Session;
use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\tools\helpers\Route;
use com\github\tncrazvan\catpaw\tools\Status;

class Payload{
    public string $username;
}

// #[\Attribute]
// class ConsumesJson extends Consumes{
//     public function __construct(){
//         parent::__construct("application/json");
//     }
// }

// #[Path("/user")]
// class User{
//     #[GET]
//     #[Path("/{username}")]
//     #[Produces("text/html")]
//     public function username(
//         #[PathParam] string $username,
//         #[Headers] array &$headers,
//         #[Session] ?array &$session
//     ):string{
//         $headers["test"] = "test";
//         $session["username"] = $username;
//         print_r($session);
//         return "hello $username!!";
//     }

//     #[POST]
//     #[Consumes("application/json")]
//     public function save(
//         Payload $user
//     ):string{
//         print_r($user);
//         return '';
//     }
// }

Route::post("/asd", #[Consumes("application/json")] function(
    #[Body] Payload $user
){
    print_r($user);
    return '';
});

//Factory::make(User::class);


// Route::notFound(function(
//     #[Status] Status $status,
//     #[Headers] array &$headers
// ){

//     $headers["Content-Type"] = "text/plain";
//     $status->setCode(Status::NOT_FOUND);
//     return "Resource not found.";
// });

$server = new CatPaw(new class extends MainConfiguration{
    public function __construct() {
        $this->show_exception = true;
        $this->show_stack_trace = false;
    }
});