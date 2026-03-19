<?php

// When running from within a parent project, use its autoloader.
// Path: tests/ -> package/ -> detain/ -> vendor/ -> autoload.php
$parentAutoloader = dirname(__DIR__, 3) . '/autoload.php';
$localAutoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($parentAutoloader)) {
    require_once $parentAutoloader;
} elseif (file_exists($localAutoloader)) {
    require_once $localAutoloader;
} else {
    die('Unable to find autoloader. Run composer install.' . PHP_EOL);
}
