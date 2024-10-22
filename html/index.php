<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaremon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="zaremon.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<?php
require_once 'conf.php';
include_once 'password.php';

session_start();

// Password protection
if (isset($PASSWORD)) {
    // Check password if submitted
    $show_wrong_password = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['password'])) {
            if ($_POST['password'] == $PASSWORD) {
                $_SESSION['auth'] = true;
            } else {
                $show_wrong_password = true;
            }
        } else {
            die('Invalid request.');
        }
    }
    
    // Show password form if not authenticated
    if (isset($PASSWORD) && !isset($_SESSION['auth'])) {
        ?>
        <body>
            <h1>
                <img src="zaremon.png" alt="Zaremon" style="height: 2em; vertical-align: middle;">
                - <?php echo $TITLE; ?>
            </h1>
            <div class="content">
                <div class="login">
                    <h2 class="login-header">
                        Prijava
                    </h2>
                    <form action="" method="post">
                        <input type="password" name="password" placeholder="Geslo" required>
                        <button type="submit">Prijava</button>
                    </form>
                    <?php if ($show_wrong_password): ?>
                        <p class="error">Napačno geslo.</p>
                    <?php endif; ?>
                    <br>
                    <div>
                        Koda:
                        <a href="https://github.com/Lenart12/Zaremon">GitHub</a>
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

$audio_files = array_diff(scandir($AUDIO_SRC_DIR, SCANDIR_SORT_DESCENDING), array('..', '.'));

$audio_records = array();

foreach ($audio_files as $file) {
    if (preg_match('/^zm_(\d+)_(\d+)\.mp3$/', $file, $matches)) {
        $date = $matches[1];
        $time = $matches[2];

        $datetime = DateTime::createFromFormat('YmdHis', $date . $time, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone($TIMEZONE));

        if ($datetime->getTimestamp() < time() - $RECORD_MAX_AGE) {
            break;
        }

        $audio_records[] = array(
            'fid' => $date . '_' . $time,
            'datetime' => $datetime,
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

$zaremon_sdr_service_active = trim(shell_exec('systemctl is-active zaremon-sdr.service')) == 'active';

?>
<body>
    <h1>
        <img src="zaremon.png" alt="Zaremon" style="height: 2em; vertical-align: middle;">
        - <?php echo $TITLE; ?>
    </h1>
    <div class="content">
        <div class="controls">
            <h2 class="controls-header">
                Nastavitve
            </h2>
            <a href="index.php">Osveži</a>
            <div class="checkbox-group">
                <input type="checkbox" id="auto-play" checked>
                <label for="auto-play">Auto-play</label>
            </div>
        </div>
        <?php if (!$zaremon_sdr_service_active): ?>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Servisni program Zaremon SDR ni aktiven!</span>
                <br>
                <span>Prosim obvesti administratorja.</span>
            </div>
        <?php endif; ?>
        <div class="recordings">
            <?php foreach ($audio_records_grouped as $date => $groups): ?>
                <div class="group-date">
                    <h2 class="gd-header">
                        <?php echo date_group_name($date) ?>
                    </h2>
                    <div class="gd-list">
                        <?php foreach ($groups as $group): ?>
                            <div class="tx-group">
                                <div class="txg-header">
                                    <?php echo tx_group_name($group); ?>
                                </div>
                                <div class="txg-list">
                                    <?php foreach ($group as $record): ?>
                                        <div class="record">
                                            <span class="time">
                                                <?php echo $record['datetime']->format('H:i:s'); ?>
                                            </span>
                                            <audio controls>
                                                <source src="audio.php?fn=<?php echo $record['fid']; ?>" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
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
                    <p>Ni posnetkov.</p>
                </div>
            <?php endif; ?>
            <br>
            <div>
                Koda:
                <a href="https://github.com/Lenart12/Zaremon">GitHub</a>
                Lenart @ 2024
            </div>
        </div>
    </div>
</body>
</html>