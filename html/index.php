<?php

# Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conf.php';
require_once 'locale.php';

if (isset($NOTIFY_DIR)) {
    require_once 'notify_common.php';
}

session_start();

// Password protection
if (isset($PASSWORD) && !isset($_SESSION['auth'])) {
    // Check password if submitted
    $show_wrong_password = false;

    $test_password = $_POST['password'] ?? $_GET['p'] ?? null;

    if ($test_password) {
        if ($test_password == $PASSWORD) {
            $_SESSION['auth'] = true;
            header("Location: $BASE_PATH");
            exit();
        } else {
            $show_wrong_password = true;
        }
    }

    // Alternative login method with MD5 hash of password (used for notifications)
    if (isset($_GET['h'])) {
        $hash_password = $_GET['h'];
        $correct_hash = get_current_notify_password_hash();

        if ($hash_password === $correct_hash) {
            $_SESSION['auth'] = true;
            header("Location: $BASE_PATH");
            exit();
        } else {
            $show_wrong_password = true;
        }
    }
    
    // Show password form if not authenticated
    if (!isset($_SESSION['auth'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>RFmon - <?= $S_LOGIN ?> </title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <link rel="icon" href="rfmon.png" type="image/png">
            <link rel="stylesheet" href="style.css">
            <script src="script.js"></script>
        </head>
        <body>
            <h1>
            <img src="rfmon.png" alt="RFmon" style="height: 2em; vertical-align: middle;">
            - <?= $S_LOGIN ?>
            </h1>
            <div class="content">
            <div class="login">
                <h2 class="login-header">
                    <i class="fas fa-sign-in-alt"></i> <?= $S_LOGIN ?>
                </h2>
                <form action="" method="post">
                    <input type="password" name="password" placeholder="<?= $S_PASSWORD ?>" required>
                    <button type="submit"><i class="fas fa-check"></i> <?= $S_LOGIN ?></button>
                </form>
                <?php if ($show_wrong_password): ?>
                <p class="error"><i class="fas fa-exclamation-circle"></i> <?= $S_WRONG_PASSWORD ?></p>
                <?php endif; ?>
                <br>
                <div>
                <i class="fas fa-code"></i> <?= $S_SOURCE_CODE ?>:
                <a href="https://github.com/Lenart12/RFmon">GitHub</a>
                Lenart @ 2024
                </div>
            </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}


$fmt = new IntlDateFormatter($LOCALE, IntlDateFormatter::RELATIVE_LONG, IntlDateFormatter::NONE);

// Pagination parameters
$current_time = time();
$page_size = isset($TIME_PER_PAGE) ? $TIME_PER_PAGE : (7 * 24 * 3600); // Default: 1 week

$to_timestamp = isset($_GET['to']) ? intval($_GET['to']) : $current_time;
$from_timestamp = isset($_GET['from']) ? intval($_GET['from']) : ($to_timestamp - $page_size);

if ($from_timestamp > $to_timestamp) {
    // From timestamp is greater than to timestamp, swap them
    $temp = $from_timestamp;
    $from_timestamp = $to_timestamp;
    $to_timestamp = $temp;
}

// If the current time is within 5 minutes of the to timestamp, set it to the current time
if (abs($current_time - $to_timestamp) < 5 * 60) {
    $to_timestamp = $current_time;
}

// Timestamps:
// prev_from | ... | prev_to | from_timestamp | ... | to_timestamp | next_from | ... | next_to

// Calculate pagination timestamps
$prev_to = $from_timestamp;
$prev_from = $prev_to - $page_size;
$next_from = $to_timestamp;
$next_to = $to_timestamp + $page_size;
$has_next = $next_from < $current_time;

$audio_files = array_diff(scandir($AUDIO_SRC_DIR, SCANDIR_SORT_DESCENDING), array('..', '.'));

$latest_audio = '';

$audio_records = array();

foreach ($audio_files as $file) {
    if (preg_match('/^zm_(\d+)_(\d+)\.mp3$/', $file, $matches)) {
        if ($latest_audio == '') {
            $latest_audio = $file;
        }

        $date = $matches[1];
        $time = $matches[2];

        $datetime = DateTime::createFromFormat('YmdHis', $date . $time, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone($TIMEZONE));

        $file_timestamp = $datetime->getTimestamp();

        if ($file_timestamp < $from_timestamp || $file_timestamp > $to_timestamp) {
            continue;
        }

        if ($SHOW_TRANSCRIPTIONS == true) {
            // Transcription file is same file except with .txt extension
            $transcription_file = $AUDIO_SRC_DIR . '/' . str_replace('.mp3', '.txt', $file);
            $transcription = file_exists($transcription_file) ? file_get_contents($transcription_file) : null;
        } 

        $audio_records[] = array(
            'fid' => $date . '_' . $time,
            'datetime' => $datetime,
            'transcription' => $transcription ?? null
        );
    }
}

$audio_records_grouped = array();

# Group records twice:
#   1. By date
#   2. By group of all records that are withing 30 seconds of each other
foreach ($audio_records as $record) {
    $date = $record['datetime']->format('Y-m-d');

    if (!isset($audio_records_grouped[$date])) {
        $audio_records_grouped[$date] = array();
    }

    $grouped = false;

    foreach ($audio_records_grouped[$date] as &$group) {
        $first_record = reset($group);

        if ($first_record['datetime']->getTimestamp() - $record['datetime']->getTimestamp() <= $TX_GROUPING_THRESHOLD) {
            $group = [$record, ...$group];
            $grouped = true;
            break;
        }
    }

    if (!$grouped) {
        $audio_records_grouped[$date][] = array($record);
    }
}

function time_range_name($from_timestamp, $to_timestamp) {
    global $fmt;
    // Create DateTime objects with @ prefix to indicate Unix timestamp
    $from_date = new DateTime('@' . $from_timestamp);
    $to_date = new DateTime('@' . $to_timestamp);
    
    // Set the timezone since @ timestamps are UTC by default
    global $TIMEZONE;
    $from_date->setTimezone(new DateTimeZone($TIMEZONE));
    $to_date->setTimezone(new DateTimeZone($TIMEZONE));
    
    $from_str = $fmt->format($from_date);
    $to_str = $fmt->format($to_date);
    return "$from_str - $to_str";
}

function date_group_name($date) {
    global $fmt;
    global $audio_records_grouped;
    $datestr = ucfirst($fmt->format(new DateTime($date)));
    $count = count($audio_records_grouped[$date]);
    return "$datestr ($count)";

}

function tx_group_name($group) {
    if (count($group) == 1) {
        return $group[0]['datetime']->format('H:i:s');
    }

    $first_record = reset($group);
    $last_record = end($group);

    return $first_record['datetime']->format('H:i:s') . 
    ' - ' . 
    $last_record['datetime']->format('H:i:s') . 
    ' (' . count($group) . ')';
}

$rfmon_service_active = trim(shell_exec('systemctl is-active rfmon.service')) == 'active';

$config_sh = __DIR__ . '/../util/config.sh';
exec($config_sh, $config_error, $config_status);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $TITLE ?> - RFmon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="rfmon.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <input type="hidden" id="latest-audio" value="<?= $latest_audio ?>">
    <h1>
        <img src="rfmon.png" alt="RFmon" style="height: 2em; vertical-align: middle;">
        - <?= $TITLE ?>
    </h1>
    <div class="content">
        <?php if (!$rfmon_service_active): ?>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= $S_RFMON_SERVICE_INACTIVE ?></span>
                <br>
                <span><?= $S_NOTIFY_ADMIN ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['notify'])): ?>
            <div class="notify">
                <i class="fas fa-bell"></i>
                <span><?= $_SESSION['notify'] ?></span>
            </div>
            <?php unset($_SESSION['notify']); ?>
        <?php endif; ?>
        <?php if ($config_status != 0): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= $S_CONFIG_ERROR ?></span>
                <br>
                <span><?= implode("<br>", $config_error) ?></span>
            </div>
        <?php endif; ?>
        <div class="notify" id="new-audio" hidden>
            <i class="fas fa-bell"></i>
            <span><?= $S_NEW_RECORDINGS ?></span> <br>
            <a href=""><i class="fas fa-sync-alt"></i> <?= $S_REFRESH ?></a>
        </div>
        <div class="recordings">
            <div class="pagination">
                <div class="pagination-group">
                    <a href="?from=<?= $prev_from ?>&to=<?= $prev_to ?>" class="pagination-button">
                        <i class="fas fa-chevron-left"></i> <?= $S_PREV_PAGE ?>
                    </a>
                    
                    <div class="pagination-date-picker">
                        <input type="date" id="date-picker" class="date-picker-input">
                        <button id="date-picker-button" class="pagination-button">
                            <i class="fas fa-calendar-alt"></i> <?= $S_GOTO_DATE ?>
                        </button>
                    </div>
                </div>
                
                <span class="pagination-info">
                    <?= time_range_name($from_timestamp, $to_timestamp) ?>
                </span>
                
                <?php if ($has_next): ?>
                <div class="pagination-group">
                    <a href="?" class="pagination-button">
                        <i class="fas fa-home"></i> <?= $S_START ?>
                    </a>
                    <a href="?from=<?= $next_from ?>&to=<?= $next_to ?>" class="pagination-button">
                        <?= $S_NEXT_PAGE ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php foreach ($audio_records_grouped as $date => &$groups): ?>
                <div class="group-date">
                    <h2 class="gd-header">
                        <i class="fas fa-calendar-alt"></i> <?php echo date_group_name($date) ?>
                    </h2>
                    <div class="gd-list">
                        <?php foreach ($groups as &$group): ?>
                            <div class="tx-group">
                                <div class="txg-header">
                                    <i class="fas fa-clock"></i> <?= tx_group_name($group) ?>
                                </div>
                                <div class="txg-list">
                                    <?php foreach ($group as $record): ?>
                                        <div class="record">
                                            <div class="record-audio">
                                                <span class="time">
                                                    <i class="fas fa-volume-up"></i><?= $record['datetime']->format('H:i:s') ?>
                                                </span>
                                                <audio controls preload="none">
                                                    <source src="audio.php?fn=<?= $record['fid'] ?>" type="audio/mpeg">
                                                    <?= $S_NO_AUDIO_SUPPORT ?>
                                                </audio>
                                            </div>
                                            <?php if (isset($record['transcription'])): ?>
                                                <div class="record-transcription">
                                                    <i class="fas fa-commenting"></i> <?= $record['transcription'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($audio_records_grouped)): ?>
                <div class="gd-header">
                    <p><i class="fas fa-info-circle"></i> <?= $S_NO_AUDIO_RECORDINGS ?></p>
                </div>
            <?php endif; ?>
            
            <div class="pagination">
                <div class="pagination-group">
                    <a href="?from=<?= $prev_from ?>&to=<?= $prev_to ?>" class="pagination-button">
                        <i class="fas fa-chevron-left"></i> <?= $S_PREV_PAGE ?>
                    </a>
                    
                    <div class="pagination-date-picker">
                        <input type="date" id="date-picker" class="date-picker-input">
                        <button id="date-picker-button" class="pagination-button">
                            <i class="fas fa-calendar-alt"></i> <?= $S_GOTO_DATE ?>
                        </button>
                    </div>
                </div>
                
                <span class="pagination-info">
                    <?= time_range_name($from_timestamp, $to_timestamp) ?>
                </span>
                
                <?php if ($has_next): ?>
                <div class="pagination-group">
                    <a href="?" class="pagination-button">
                        <i class="fas fa-home"></i> <?= $S_START ?>
                    </a>
                    <a href="?from=<?= $next_from ?>&to=<?= $next_to ?>" class="pagination-button">
                        <?= $S_NEXT_PAGE ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="controls">
                <h2 class="controls-header">
                    <i class="fas fa-cogs"></i> <?= $S_SETTINGS ?>
                </h2>
                <div class="controls-content">
                    <a href=""><i class="fas fa-sync-alt"></i> <?= $S_REFRESH ?></a>
                    <div class="checkbox-group">
                        <input type="checkbox" id="auto-play" checked>
                        <label for="auto-play"><i class="fas fa-play"></i> <?= $S_AUTOPLAY ?></label>
                    </div>
                    <?php if (isset($NOTIFY_DIR)): ?>
                        <div class="subscribe">
                            <form action="notify.php" method="post">
                                <label for="ne"><i class="fas fa-envelope"></i> <?= $S_SUBSCRIBE_NOTIFICATIONS ?></label>
                                <br>
                                <input id="ne" type="email" name="s" placeholder="<?= $S_ENTER_EMAIL ?>" required>
                                <button type="submit"><i class="fas fa-paper-plane"></i> <?= $S_SUBSCRIBE ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <br>
            <div>
                <i class="fas fa-code"></i> <?= $S_SOURCE_CODE ?>:
                <a href="https://github.com/Lenart12/RFmon">GitHub</a>
                Lenart @ 2024
            </div>
        </div>
    </div>
</body>
</html>