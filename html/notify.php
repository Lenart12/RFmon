<?php

require_once 'conf.php';
require_once 'locale.php';

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

#### SCRIPT MAIN ####
# When called from command line, send notify emails to all subscribers
if (isset($argv[1])) {
    $rx_file = $argv[1];
    $rx_time = time();

    # Lock the file and get old received time and set new received time
    $fp = fopen($NOTIFY_LAST_RECEIVED_TIME, 'c+');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $rx_time);
        fflush($fp);

        # Also write the received file to the pending file
        file_put_contents($NOTIFY_WAIT_FOR_MORE_PENDING_FILE, $rx_file . "\n", FILE_APPEND);

        flock($fp, LOCK_UN);
    } else {
        die("Couldn't lock file! $NOTIFY_LAST_RECEIVED_TIME");
    }
    fclose($fp);

    # Wait if the script is called multiple times in a short period
    $sleep_time = $NOTIFY_WAIT_FOR_MORE;
    if (isset($argv[2])) {
        $sleep_time = intval($argv[2]);
    }
    sleep($sleep_time);

    # If last received time has changed while sleeping, then another script has been called
    # and we should exit to avoid sending multiple emails
    $new_rx_time = file_get_contents($NOTIFY_LAST_RECEIVED_TIME, LOCK_EX);
    if ($new_rx_time != $rx_time) {
        exit();
    }

    // Check if last sent time is within timeout
    $last_sent_time = file_get_contents($NOTIFY_LAST_SENT_TIME, LOCK_EX) ?: 0;
    file_put_contents($NOTIFY_LAST_SENT_TIME, $rx_time, LOCK_EX);
    if (time() - intval($last_sent_time) < $NOTIFY_TIMEOUT) {
        // Reset timeout to avoid sending multiple emails
        // when called multiple times in a short period
        exit();
    }

    $emails = file_get_contents($NOTIFY_MAILINGLIST);
    if (!$emails) {
        exit();
    }
    $emails = explode("\n", $emails);

    $pending_files = file($NOTIFY_WAIT_FOR_MORE_PENDING_FILE, FILE_IGNORE_NEW_LINES);
    $pending_files = array_unique($pending_files);
    file_put_contents($NOTIFY_WAIT_FOR_MORE_PENDING_FILE, '');

    $NEW_RX_COUNT = count($pending_files);
    $NEW_TRANSCRIPTIONS = '';
    
    if ($SHOW_TRANSCRIPTIONS) {
        $transcriptions = array();

        function empty_transcription($transcription) {
            if (empty($transcription)) {
                return true;
            }
            $transcription = preg_replace('/\s+/', '', $transcription);
            $transcription = preg_replace('/<.+?>/', '', $transcription);
            $transcription = trim($transcription);
            return empty($transcription);
        }

        foreach ($pending_files as $file) {
            $transcription_file = str_replace('.mp3', '.txt', $file);
            $transcription = file_get_contents($transcription_file);
            # If not transcribed or transcription is empty, skip
            if (empty_transcription($transcription)) {
                continue;
            }
            $transcriptions[] = $transcription;
        }

        if (count($transcriptions) == 0) {
            die("No interesting transcriptions found.");
        }

        $NEW_TRANSCRIPTIONS = '<ul>';
        foreach ($transcriptions as $transcription) {
            $NEW_TRANSCRIPTIONS .= "<li>$transcription</li>";
        }
        $NEW_TRANSCRIPTIONS .= '</ul><br>';
    }


    $sent_count = 0;
    foreach ($emails as $email) {
        if (empty($email)) {
            continue;
        }
        $sent_count++;

        $tr_prop = array(
            'EMAIL' => $email,
            'TITLE' => $TITLE,
            'NEW_RX_COUNT' => $NEW_RX_COUNT,
            'NEW_TRANSCRIPTIONS' => $NEW_TRANSCRIPTIONS,
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
