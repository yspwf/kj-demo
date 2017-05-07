<?php 
namespace Core\Lib\DB;

class DbFactory{

      public static function factory($db_type="mysql"){

              $db_type = strtolower(!empty($GLOBALS['config']['DB_TYPE']) ? $GLOBALS['config']['DB_TYPE'] : $db_type);
              
              switch($db_type){
              	   case 'mysql':
              	       $classname = 'Mysql';
              	       break;
              	   default :
              	       exit('Error:DBtype is error');    
              }

              $class = 'Core\Lib\DB\\'.$classname;
              return new $class();
      } 

}

?>