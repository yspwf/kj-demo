<?php 
namespace Core\Lib\DB;


class Model implements DbInterface{

     public function getInstance(){
     	 $DB = Mysql::getInstance();
     	 $DB->getConnect();
     }

}


?>