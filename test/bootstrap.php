<?php

error_reporting(E_ALL | E_STRICT);

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
$testCase = 'all';
// Setup autoloading
require 'vendor/autoload.php';
