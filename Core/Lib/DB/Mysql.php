<?php 
namespace Core\Lib\DB;
use PDO;

class Mysql implements DbInterface{
      
      private $pdo = null;
      private static $_instance = [];

      public static function db(){
      	    if(!class_exists('PDO')){
      	    	 throw new Exception('not found PDO');
      	         return false;
      	    }

            $options_arr = array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES UTF8',PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC);

            try{
            	$dns = $GLOBALS['config']['DB_TYPE'].':host='.$GLOBALS['config']['DB_HOST'].';dbname='.$GLOBALS['config']['DB_NAME'];
            	$pdo = new PDO($dns, $GLOBALS['config']['DB_USER'], $GLOBALS['config']['DB_PASSWORD'],$options_arr);
            	var_dump($pdo);
            }catch(PDOException $e){
            	 throw new Exception($e->getMessage());
            	 return false;
            }

            if(!$pdo){
            	throw new Exception('PDO CONNECT ERROR');
            	return false;
            }	   
            return $pdo;
      }


      /***
          得到操作数据库对象
      */
      public static function getInstance(){

             if(!isset(self::$_instance['mysql']) || !is_object(self::$_instance['mysql'])){
             	 self::$_instance['mysql'] = new self();
             }
             return self::$_instance['mysql'];

      }    

      public function getConnect(){
            $this->pdo = self::db();
      } 

     /***
       查询操作
     */   
      public function query($sql){
      	  echo "sdsfsfdsf";
      	   if(empty($sql)){
      	   	   return false;
      	   }
      	   try{
      	   	   $this->pdo->exec($sql);
      	   }catch(Exception $e){
      	   	   throw new Exception($e->getMessage()); 
      	   }
      } 

      public function delete($table, $where){
      	   if(empty($table) || empty($where)){
      	   	    return false;
      	   }
      	   $sql = "delete from {$table} where ".$where;
      	   return $this->query($sql);
      }


}


?>