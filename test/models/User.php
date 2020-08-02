<?php
namespace com\github\tncrazvan\catpaw\test\models\homepage;
use com\github\tncrazvan\catpaw\http\HttpRequestBody;

class User extends HttpRequestBody{
    public $username;
    public $email;
}