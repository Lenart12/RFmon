<?php

require_once 'conf.php';
require_once 'locale.php';

// All variables for email notifications must be set
foreach (['NOTIFY_DIR', 'NOTIFY_TIMEOUT', 'NOTIFY_WAIT_FOR_MORE', 'NOTIFY_FROM', 'NOTIFY_LINK_HOST', 'NOTIFY_AUTO_LOGIN'] as $var) {
    if (!isset($$var)) {
        echo "$var not set.";
        exit();
    }
}

// check if notify directory exists and is writable
if (!is_dir($NOTIFY_DIR)) {
    echo "Notify directory not found. $NOTIFY_DIR";
    exit();
}

if (!is_writable($NOTIFY_DIR)) {
    echo "Notify directory not writable. $NOTIFY_DIR";
    exit();
}

$NOTIFY_MAILINGLIST = $NOTIFY_DIR . '/notify.txt';
$NOTIFY_LAST_SENT_TIME = $NOTIFY_DIR . '/notify_last_sent.txt';
$NOTIFY_LAST_RECEIVED_TIME = $NOTIFY_DIR . '/notify_last_rx.txt';
$NOTIFY_WAIT_FOR_MORE_PENDING_FILE = $NOTIFY_DIR . '/notify_wait_for_more_pending.txt';

function add_notify_mailinglist($email) {
    global $NOTIFY_MAILINGLIST;
    $emails = file_get_contents($NOTIFY_MAILINGLIST);
    $emails = explode("\n", $emails);
    if (in_array($email, $emails)) {
        return false;
    }
    file_put_contents($NOTIFY_MAILINGLIST, $email . "\n", FILE_APPEND);
    return true;
}

function remove_notify_mailinglist($email) {
    global $NOTIFY_MAILINGLIST;
    $emails = file_get_contents($NOTIFY_MAILINGLIST);
    $emails = explode("\n", $emails);
    $emails = array_diff($emails, array($email));
    file_put_contents($NOTIFY_MAILINGLIST, implode("\n", $emails));
}

function render_translation($str, $props) {
    foreach ($props as $key => &$val) {
        $str = str_replace("%$key%", $val, $str);
    }
    return $str;
}

function empty_transcription($transcription) {
    if (empty($transcription)) {
        return true;
    }
    $transcription = preg_replace('/\s+/', '', $transcription);
    $transcription = preg_replace('/<.+?>/', '', $transcription);
    $transcription = trim($transcription);
    return empty($transcription);
}

function get_current_notify_password_hash() {
    global $NOTIFY_LAST_SENT_TIME;
    global $PASSWORD;
    $notify_last_sent = file_get_contents($NOTIFY_LAST_SENT_TIME) ?: 0;
    return md5("rfmon" . $PASSWORD . $notify_last_sent);
}
