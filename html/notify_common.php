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
    global $PASSWORD;
    return md5("rfmon" . $PASSWORD );
}

function send_notification_email($recipients, $pending_files) {
    global $SHOW_TRANSCRIPTIONS;
    global $S_UNTRANSCRIBED_RECORDINGS;
    global $S_RECORDINGS_WITHOUT_DIALOG;
    global $S_NOTIFY_EMAIL_SUBJECT;
    global $S_NOTIFY_EMAIL_BODY;
    global $NOTIFY_LINK_HOST;
    global $NOTIFY_AUTO_LOGIN;
    global $TITLE;
    global $NOTIFY_FROM;

    $NEW_TRANSCRIPTIONS = '';
    
    if ($SHOW_TRANSCRIPTIONS) {
        $transcriptions = array();
        $untranscribed_count = 0;
        $empty_count = 0;

        foreach ($pending_files as $file) {
            $transcription_file = str_replace('.mp3', '.txt', $file);

            if (!file_exists($transcription_file) || !is_readable($transcription_file) || !str_ends_with($transcription_file, '.txt')) {
                $untranscribed_count++;
                continue;
            }

            $transcription = file_get_contents($transcription_file);
            # If not transcribed or transcription is empty, skip
            if (empty_transcription($transcription)) {
                $empty_count++;
                continue;
            }
            $transcriptions[] = $transcription;
        }

        $NEW_TRANSCRIPTIONS = '<ul>';
        foreach ($transcriptions as $transcription) {
            $NEW_TRANSCRIPTIONS .= "<li>$transcription</li>";
        }
        if ($untranscribed_count > 0) {
            $NEW_TRANSCRIPTIONS .= "<li>$untranscribed_count $S_UNTRANSCRIBED_RECORDINGS</li>";
        }
        if ($empty_count > 0) {
            $NEW_TRANSCRIPTIONS .= "<li>$empty_count $S_RECORDINGS_WITHOUT_DIALOG</li>";
        }
        $NEW_TRANSCRIPTIONS .= '</ul><br>';
    }

    $sent_count = 0;
    foreach ($recipients as $email) {
        if (empty($email)) {
            continue;
        }
        $sent_count++;

        $link_host = $NOTIFY_LINK_HOST;

        if ($NOTIFY_AUTO_LOGIN) {
            $correct_hash = get_current_notify_password_hash();
            $link_host .= "?h=$correct_hash";
        }

        $tr_prop = array(
            'EMAIL' => $email,
            'TITLE' => $TITLE,
            'NEW_RX_COUNT' => count($pending_files),
            'NEW_TRANSCRIPTIONS' => $NEW_TRANSCRIPTIONS,
            'NOTIFY_LINK_HOST' => $link_host,
        );
        
        $subject = render_translation($S_NOTIFY_EMAIL_SUBJECT, $tr_prop);
        $body = render_translation($S_NOTIFY_EMAIL_BODY, $tr_prop);

        $headers = "From: $NOTIFY_FROM\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $body, $headers);
    }

    echo "Sent " . $sent_count . " notify emails.\n";
}