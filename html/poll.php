<?php

require_once 'conf.php';

$NOTIFY_LATEST_FILE = $NOTIFY_DIR . '/notify_latest_file.txt';

$lastest_file = file_get_contents($NOTIFY_LATEST_FILE);
if ($lastest_file === false) {
    echo "Failed to read latest file.";
    exit();
}
$lastest_file = trim($lastest_file);
if (empty($lastest_file)) {
    echo "No latest file.";
    exit();
}
echo $lastest_file;
