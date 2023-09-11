<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

use App\Handler\Console;

/*
* Define constants
*/
if (!defined("ROOT")) define("ROOT", __DIR__);

if (!defined("DS")) define("DS", DIRECTORY_SEPARATOR);

if (!defined("APP_ROOT")) define("APP_ROOT", ROOT.DS."app");

require ROOT . DS . 'vendor' . DS . 'autoload.php';

// ERROR HANDLING

/*
 * Create pretty-print errors
 */
error_reporting(-1);
ini_set('display_errors', 'Off');

set_error_handler(function ($code, $message) {
	// convert error to ErrorException
	throw new ErrorException($message, $code);
});

register_shutdown_function(function () {
	// check if the script ended up with an error
	$lastError = error_get_last();
	$fatal_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
	if ($lastError && in_array($lastError['type'], $fatal_errors, true)) {
		// Ok script ended with a problem:
		// let's do something about it
		// handle last error ...
	}
});

set_exception_handler(function (Throwable $exception) {
	$console = new Console();
	$monolog = new Logger('bookScan');
	$monolog->pushHandler(new MonologStreamHandler(ROOT . DS . "logs" . DS . "app.log", Logger::DEBUG));

	$monolog->error($exception->getMessage(), [
		'trace' => $exception->getTraceAsString(),
	]);

	$console->output("ERROR: " . $exception->getMessage(), "brightRed");
});


// Load env
$env_path = ROOT . DS . ".env";
if (file_exists($env_path)) {
	$dotenv = new \josegonzalez\Dotenv\Loader([$env_path]);
	$dotenv->parse()
	->putenv()
	->toEnv()
	->toServer();
} else {
	throw new Exception("You need to set the .env file");
}

// ERROR HANDLING [END]