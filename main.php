<?php

require_once(__DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php");

use App\Application;

$app = new Application();

// Init app
$app->init($argv);