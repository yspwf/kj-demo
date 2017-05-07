<?php 
namespace Core;

class App{
        
        const CORE_PATH = __DIR__;  
   
        public function run(){
        	 //设置头部文件
        	 $this->_header();

        	 //自动载入函数
        	 $this->_setAutoload();
             
              //载入系统配置文件
             $this->_loadSysFile();

             //设置路由
             $this->_setRoute();

            
        }


        /**
        * 头部文件 header
        */
        public function _header(){
        	header('Content-type:text/html; charset=utf-8;');
        }

        /**
        */
        private function _loadSysFile(){
        	$GLOBALS['config'] = include self::CORE_PATH.'/Config/Config.php';
        	include self::CORE_PATH.'/Lib/Function/Function.php';

        }

        /**
        *自动载入函数
        */
        public function _setAutoload(){
        	require __DIR__.'/Lib/Autoload.php';
        	$auto = new Lib\Autoload();
        	$auto->register();
        }   

        public function _setRoute(){
        	$route = new Lib\Route();
        	$route->parse();
        }

}


?>