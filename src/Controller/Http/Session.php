<?php
namespace com\github\tncrazvan\catpaw\controller\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpController;


class Session extends HttpController{
    
    public function main() {
        switch($this->getClientMethod()){
            case "DELETE":
                return $this->delete();
            break;
            case "PUT":
                return $this->put();
            break;
            case "POST":
                return $this->post();
            break;
            case "GET":
                return $this->get();
            break;
            default:
                $this->setStatus(Status::BAD_REQUEST);
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
        $_SESSION = &$this->startSession();
        $queries = $this->getUrlQueries();
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
        $_SESSION = &$this->startSession();
        foreach($this->getUrlQueries() as $key => &$value){
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
        $_SESSION = &$this->startSession();
        foreach($this->getUrlQueries() as $key => &$value){
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
        $_SESSION = &$this->startSession();
        $keys = array_keys($queries);
        $queries = $this->getUrlQueries();
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