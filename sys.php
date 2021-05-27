<?php

// Or, using an anonymous function
spl_autoload_register(function ($class) {
    include 'classes/' . $class . '.class.php';
});

$dbgBuffer = '';
function dbg(string $msg, ...$var) : void {
	return;
	$GLOBALS['dbgBuffer'] .= $msg . " : " . print_r($var, true);
}

function asserts(string $desc, $expr, bool $expectedResult) : void {
	if ($expr === $expectedResult) {
		echo "PASS " . $desc . "\n";
	} else {
		echo "FAIL " . $desc . "\n";
	}
}

function mylog(string $msg) {
	file_put_contents('./my.log', date("Y-m-d H:i:s") ." (". round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 2) . ") #". REQID ."# ". $msg ."\n", FILE_APPEND);
}
