<?php 
namespace Core\Lib;

class Autoload{


        public function register(){
            spl_autoload_register(array($this,'autoload'));
        }

        public function autoload($classname){
        	   
        	   try{
        	   	   $pathArr = explode("\\",$classname);
        	   	   $filename = array_pop($pathArr);
                   $dir = implode(DIRECTORY_SEPARATOR, $pathArr);
                   
        	   	   $filepath = str_replace("\\", "/", $dir.'/'.$filename.'.php');
        	   	   require_once APP_PATH.'/'.$filepath;
        	   }catch(Exception $e){
        	   	    echo $e->getMessage();
        	   }
        }
}


?>