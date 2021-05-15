<?php

// Or, using an anonymous function
spl_autoload_register(function ($class) {
    include 'classes/' . $class . '.class.php';
});

function dbg(string $msg, ...$var) : void {
	//return;
	echo $msg . " : " . print_r($var, true);
}

function asserts(string $desc, $expr, bool $expectedResult) : void {
	if ($expr === $expectedResult) {
		echo "PASS " . $desc . "\n";
	} else {
		echo "FAIL " . $desc . "\n";
	}
}

