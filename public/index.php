<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require(__DIR__ . '/../SM/SmBase.php');

SM::start();
$loader = SM::getLoader();
$loader->setPsr4('App\\', APP_PATH);

(new App\App)->run();
