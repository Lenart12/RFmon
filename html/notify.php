<?php

require_once 'conf.php';
require_once 'locale.php';

$NOTIFY_MAILINGLIST = $NOTIFY_DIR . '/notify.txt';
$NOTIFY_LAST_RECEIVED_TIME = $NOTIFY_DIR . '/notify_last_rx.txt';

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

#### SCRIPT MAIN ####
# When called from command line, send notify emails to all subscribers
if (isset($argv[1])) {   
    $last_rx_time = file_get_contents($NOTIFY_LAST_RECEIVED_TIME);
    if (!$last_rx_time) {
        $last_rx_time = 0;
    }

    // Check if last sent time is within timeout
    if (time() - intval($last_rx_time) < $NOTIFY_TIMEOUT) {
        // Reset timeout to avoid sending multiple emails
        // when called multiple times in a short period
        file_put_contents($NOTIFY_LAST_RECEIVED_TIME, time());
        exit();
    }

    $emails = file_get_contents($NOTIFY_MAILINGLIST);
    if (!$emails) {
        exit();
    }

    file_put_contents($NOTIFY_LAST_RECEIVED_TIME, time());

    $emails = explode("\n", $emails);
    $sent_count = 0;
    foreach ($emails as $email) {
        if (empty($email)) {
            continue;
        }
        $sent_count++;

        $tr_prop = array(
            'EMAIL' => $email,
            'TITLE' => $TITLE,
            'NOTIFY_LINK_HOST' => $NOTIFY_LINK_HOST,
        );
        
        $subject = render_translation($S_NOTIFY_EMAIL_SUBJECT, $tr_prop);
        $body = render_translation($S_NOTIFY_EMAIL_BODY, $tr_prop);

        $headers = "From: $NOTIFY_FROM\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $body, $headers);
    }

    echo "Sent " . $sent_count . " notify emails.\n";

    exit();
}

#### UNSUBSCRIBE ####
if (isset($_GET['r'])) {
    $email = $_GET['r'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email.";
        exit();
    }

    remove_notify_mailinglist($_GET['r']);
    echo "Odjavljen...";
    exit();
}

#### SUBSCRIBE ####
if (isset($_POST['s'])) {
    if (isset($PASSWORD)) {
        session_start();
        if (!isset($_SESSION['auth'])) {
            echo "Unauthorized.";
            exit();
        }
    }

    $email = $_POST['s'];
    # Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email.";
        exit();
    }

    if (add_notify_mailinglist($email) || true) {
        $FILE = __DIR__ . '/rfmon.png';
        $img = file_get_contents($FILE);
        $RFMON_IMG = '<img src="data:image/png;base64,' . base64_encode($img) . '" height="80">';

        $tr_prop = array(
            'EMAIL' => $email,
            'NOTIFY_LINK_HOST' => $NOTIFY_LINK_HOST,
            'RFMON_IMG' => $RFMON_IMG
        );

        $subject = render_translation($S_SUBSCRIBE_CONFIRM_SUBJECT, $tr_prop);
        $body = render_translation($S_SUBSCRIBE_CONFIRM_BODY, $tr_prop);

        $headers = "From: $NOTIFY_FROM\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        mail($email, $subject, $body, $headers);
        $_SESSION['notify'] = $S_SUBSCRIBE_SUCCESS;
    } else {
        $_SESSION['notify'] = $S_SUBSCRIBE_ALREADY_SUBSCRIBED;
    }
    header('Location: index.php');
    exit();
}
