<?php

require_once 'conf.php';

if (!isset($LOCALE)) {
    $LOCALE = 'en_US';
}

$locale_file = __DIR__ . "/locale/$LOCALE.php";

if (file_exists($locale_file)) {
    require_once $locale_file;
} else {
    require_once __DIR__ . '/locale/en_US.php';
}