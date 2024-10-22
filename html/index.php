<?php
require_once 'conf.php';

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaremon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="zaremon.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        h1 {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            margin: 0;
        }
        @media screen and (min-width: 768px) {
            .content {
                width: 75%;
                margin: 0 auto;
            }
            
        }
        .recordings {
            padding: 20px;
        }
        .group-date {
            margin-bottom: 20px;
        }
        .gd-header {
            background-color: #2196F3;
            color: white;
            padding: 10px;
            margin: 0;
        }
        .gd-list {
            border: 1px solid #ddd;
            padding: 10px;
            background-color: white;
        }
        .tx-group {
            margin-bottom: 10px;
        }
        .txg-header {
            background-color: #f1f1f1;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .txg-list {
            padding: 10px;
        }
        .record {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .record .time {
            margin-right: 10px;
            font-weight: bold;
        }
        audio {
            width: 100%;
        }
        .controls {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .controls-header {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.5);
        }

        .checkbox-group label {
            font-size: 1.2em;
        }
        .controls a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .controls a:hover {
            background-color: #45a049;
        }
    </style>
</head>
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
    <script>
        document.querySelectorAll('audio').forEach(function(audio) {
            audio.addEventListener('ended', function() {
                if (!document.getElementById('auto-play').checked) {
                    return;
                }
                var next = audio.closest('.record').nextElementSibling;
                if (next && next.querySelector('audio')) {
                    next.querySelector('audio').play();
                }
            });
        });
    </script>
</body>
</html>