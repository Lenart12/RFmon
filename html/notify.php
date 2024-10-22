<?php

require_once 'conf.php';

$NOTIFY_MAILINGLIST = 'notify.txt';
$NOTIFY_LAST_SENT_TIME = 'notify_lastsent.txt';

$NOTIFY_MAILINGLIST = $NOTIFY_DIR . '/notify.txt';
$NOTIFY_LAST_SENT_TIME = $NOTIFY_DIR . '/notify_lastsent.txt';

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

#### SCRIPT MAIN ####
# When called from command line, send notify emails to all subscribers
if (isset($argv[1])) {   
    $last_sent_time = file_get_contents($NOTIFY_LAST_SENT_TIME);
    if (!$last_sent_time) {
        $last_sent_time = 0;
    }

    if (time() - intval($last_sent_time) < $NOTIFY_TIMEOUT) {
        exit();
    }

    $emails = file_get_contents($NOTIFY_MAILINGLIST);
    if (!$emails) {
        exit();
    }

    file_put_contents($NOTIFY_LAST_SENT_TIME, time());

    $emails = explode("\n", $emails);
    $sent_count = 0;
    foreach ($emails as $email) {
        if (empty($email)) {
            continue;
        }
        $sent_count++;
        
        $subject = $NOTIFY_SUBJECT;
        $message = "Nove posnetke lahko poslušate na <a href=\"$NOTIFY_LINK_HOST\">$TITLE</a>.<br><br><a href=\"$NOTIFY_LINK_HOST/notify.php?r=$email\">Odjava</a>";
        $headers = "From: $NOTIFY_FROM\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $message, $headers);
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

    if (add_notify_mailinglist($email)) {
        $subject = "Potrditev prijave na obvestila";
        $message = "Za odjavo od obvestil klikni <a href=\"$NOTIFY_LINK_HOST/notify.php?r=$email\">tukaj</a>.";
        $headers = "From: $NOTIFY_FROM\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $message, $headers);
        $_SESSION['notify'] = 'Prijava uspešna. Preveri svoj email.';
    } else {
        $_SESSION['notify'] = 'Email je že prijavljen.';
    }
    header('Location: index.php');
    exit();
}
