<?php
namespace App\Http;

use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Tools\Status;
use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpController;


class Session extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        switch($e->getClientMethod()){
            case "DELETE":
                return $this->delete($e);
            break;
            case "PUT":
                return $this->put($e);
            break;
            case "POST":
                return $this->post($e);
            break;
            case "GET":
                return $this->get($e);
            break;
            default:
                $e->setStatus(Status::BAD_REQUEST);
            break;
        }
    }

    /**
     * Iterates through all the URL queries and unset the relative session fields.
     * If a certain field doesn't exist the method will silently skip it.
     * If no queries are specified, the method will simply unset all fields in the session array.
     * @return void
     */
    protected function delete(HttpEvent &$e):void{
        $_SESSION = &$e->startSession();
        $queries = $e->getUrlQueries();
        $keys = array_keys($queries);
        if(\count($keys) === 0) {
            foreach(array_keys($_SESSION) as &$key){
                unset($_SESSION[$key]);
            }
        }else{
            foreach($keys as &$key){
                if(!isset($_SESSION[$key])) continue;
                unset($_SESSION[$key]);
            }
        }
        
    }

    /**
     * Iterates through all the URL queries and updates the relative session fields with the specified values.
     * If a certain field doesn't exist, the method will silently skip it.
     * use Session::post to make new fields instead.
     * @return void
     */
    protected function put(HttpEvent &$e):void{
        $_SESSION = &$e->startSession();
        foreach($e->getUrlQueries() as $key => &$value){
            if(!isset($_SESSION[$key])) continue;
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Iterates through all the URL queries and makes new session fields using the specified values.
     * If a certain field already exists the method will silently skip and NOT update it.
     * Use Session::put to update fields instead.
     * @return void
     */
    protected function post(HttpEvent &$e):void{
        $_SESSION = &$e->startSession();
        foreach($e->getUrlQueries() as $key => &$value){
            if(isset($_SESSION[$key])) continue;
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Iterates though all the URL queries and return the appropriate session fields.
     * If a certain request field doesn't exist the method will silently skip it.
     * If no queries are specified, the method will simply return the whole session array.
     * @return string the selected session field in a json format.
     */
    protected function get(HttpEvent &$e):string{
        $_SESSION = &$e->startSession();
        $keys = array_keys($queries);
        $queries = $e->getUrlQueries();
        $len_queries = count($keys);
        if($len_queries > 0){
            $result = [];
            foreach($keys as &$key){
                if(!isset($_SESSION[$key])){
                    continue;
                }
                $result[$key] = $_SESSION[$key];
            }
            return \json_encode($result);
        }else{
            return \json_encode($_SESSION);
        }

    }
    public function onClose():void {}
}