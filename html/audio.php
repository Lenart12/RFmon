<?php

require_once("conf.php");

$fn = $_GET['fn'];

if (!preg_match('/^\d+_\d+$/', $fn)) {
    die("Invalid filename.");
}

$fn = $AUDIO_SRC_DIR . "/" . "zm_$fn.mp3";

if (!file_exists($fn)) {
    die("File not found.");
}

header("Content-Type: audio/mpeg");
header("Content-Length: " . filesize($fn));
header("Content-Disposition: inline; filename=\"" . basename($fn) . "\"");

readfile($fn);

?>