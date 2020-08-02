<?php
namespace app;
use com\github\tncrazvan\catpaw\http\HttpRequestBody;

class User extends HttpRequestBody{
    public $username;
    public $email;
}