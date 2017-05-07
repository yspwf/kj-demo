<?php 
namespace Core\Lib;

class Route{

     /**
       分析URL
     */
      public function parse(){
      	  /*var_dump( $url = $_SERVER['PHP_SELF']);
      	  $num =strpos("$url",'.php')+5;*/
      	  
          $pathInfo = !empty($_SERVER['PATH_INFO']) ? explode('/',$_SERVER['PATH_INFO']) : array();
          $appName = !empty($pathInfo['1']) ? $pathInfo['1'] : getConfig('DEFAULT_APP_NAME');
          $className = !empty($pathInfo['2']) ? $pathInfo['2'] : getConfig('DEFAULT_CONTROLLER');
          $method = !empty($pathInfo['3']) ? $pathInfo['3'] : getConfig('DEFAULT_METHOD');  
          $objFile = $appName.'\Controller\\'.$className.'Controller';
          $obj = new $objFile();
          
          try{
          	call_user_func(array($obj,$method));
          }catch(EXCEPTION $e){
          	   echo $e->getMessage();
          }  
      }

}



?>