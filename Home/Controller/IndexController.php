<?php 
namespace Home\Controller;
use Core\Lib\Controller;
use Core\Lib\DB\Model;
class IndexController extends Controller{
     
     public function _init(){
     	echo "init";
     }

     public function index(){
             $m = new Model();
             $m->getInstance();
     }

}

?>