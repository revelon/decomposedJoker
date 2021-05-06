<?php

// Or, using an anonymous function
spl_autoload_register(function ($class) {
    include 'classes/' . $class . '.class.php';
});

function dbg($msg, ...$var) {
	//return;
	echo $msg . " : " . print_r($var, true);
}

function asserts($desc, $expr, $expectedResult) {
	if ($expr === $expectedResult) {
		echo "PASS " . $desc . "\n";
	} else {
		echo "FAIL " . $desc . "\n";
	}
}

