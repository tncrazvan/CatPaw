<?php
require_once './vendor/autoload.php';

use com\github\tncrazvan\catpaw\attributes\http\Headers;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\PathParam;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\helpers\Route;
use com\github\tncrazvan\catpaw\tools\Status;


define('PATH_PARAMS', []);

define('METHODS', []);
define('METHODS_ATTRIBUTES', []);

define('METHODS_ARGS', []);
define('METHODS_ARGS_ATTRIBUTES', []);

define('FUNCTIONS', []);
define('FUNCTIONS_ATTRIBUTES', []);

define('FUNCTIONS_ARGS', []);
define('FUNCTIONS_ARGS_ATTRIBUTES', []);

define('KLASS', []);
define('CLASS_ATTRIBUTES', []);


#[Path("/user/{username}")]
class User{
    #[GET]
    #[Produces("text/html")]
    public function username(
        #[PathParam] string $username,
        #[Headers] array &$headers
    ):string{
        $headers["test"] = "test";
        return "hello $username!!";
    }
}

Factory::make(User::class);


Route::notFound(function(
    #[Status] Status $status
){
    $status->setCode(404);
    return "Resource not found.";
});

$server = new CatPaw(8080);