<?php 

if(PHP_VERSION < 5.4){
	  exit('亲！请升级的你的php版本！');
}

define('APP_PATH',__DIR__);

require APP_PATH.'/Core/App.php';

$app = new Core\App();
$app->run();


?>