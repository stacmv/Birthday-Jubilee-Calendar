# Dockerfile - PHP 8.4 Birthday Jubilee Calendar Web App
#
# How to build:
#   docker build -t php-jubilee-calendar .
#
# How to run:
#   docker run --rm -p 8080:8080 php-jubilee-calendar
#
# Then open http://localhost:8080/ in your browser.

FROM php:8.4-cli

WORKDIR /app

# Записываем многосрочный PHP-скрипт в index.php внутри контейнера
RUN cat << 'EOF' > ./index.php
<?php

function plural_form($n, $forms) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $forms[2];
    if ($n1 > 1 && $n1 < 5) return $forms[1];
    if ($n1 == 1) return $forms[0];
    return $forms[2];
}

function format_duration($seconds) {
    $years = floor($seconds / (365.2425 * 86400));
    $seconds -= $years * 365.2425 * 86400;
    $days = floor($seconds / 86400);

    $parts = [];
    if ($years > 0) {
        $parts[] = $years . ' ' . plural_form($years, ['год', 'года', 'лет']);
    }
    if ($days > 0) {
        $parts[] = $days . ' ' . plural_form($days, ['день', 'дня', 'дней']);
    }
    if (empty($parts)) {
        return '0 дней';
    }
    return implode(' ', $parts);
}

function format_date_rus(DateTime $dt, $withTime = false) {
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта',
        4 => 'апреля', 5 => 'мая', 6 => 'июня',
        7 => 'июля', 8 => 'августа', 9 => 'сентября',
        10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];
    $d = $dt->format('j');
    $m = $months[(int)$dt->format('n')];
    $y = $dt->format('Y');
    $dateStr = "$d $m $y года";
    if ($withTime) {
        $h = (int)$dt->format('G');
        $dateStr .= " в $h часов";
    }
    return $dateStr;
}

// Parse GET parameters
$birthDateRaw = $_GET['bday'] ?? null;
$birthTimeRaw = $_GET['btime'] ?? null;

// Validate birth date
$birthDate = null;
$birthDateTime = null;

if ($birthDateRaw) {
    $birthDate = DateTime::createFromFormat('Y-m-d', $birthDateRaw);
    if (!$birthDate) {
        $error = "Неверный формат даты рождения. Используйте ГГГГ-ММ-ДД.";
    }
}

if ($birthDate && $birthTimeRaw) {
    $timeFormats = ['H:i:s', 'H:i'];
    $time = false;
    foreach ($timeFormats as $fmt) {
        $time = DateTime::createFromFormat($fmt, $birthTimeRaw);
        if ($time) break;
    }
    if ($time) {
        $birthDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $birthDate->format('Y-m-d') . ' ' . $time->format('H:i:s'));
    } else {
        $birthDateTime = clone $birthDate;
    }
} else if ($birthDate) {
    $birthDateTime = clone $birthDate;
}

if (!$birthDate) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Календарь юбилеев</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f0f8ff; padding: 2em; max-width: 480px; margin: auto; }
            h1 { color: #2471A3; }
            label { display: block; margin-top: 1em; }
            input { padding: 0.4em; font-size: 1em; width: 100%; box-sizing: border-box; }
            button { margin-top: 1.4em; padding: 0.6em 1em; font-size: 1em; background: #2471A3; color: white; border: none; cursor: pointer; }
            button:hover { background: #1b4b72; }
            .note { font-size: 0.9em; color: #555; margin-top: 0.3em; }
        </style>
    </head>
    <body>
        <h1>Календарь моих юбилеев</h1>
        <form method="GET">
            <label for="bday">Дата рождения (обязательно):<br>
                <input type="date" id="bday" name="bday" required>
            </label>
            <label for="btime">Время рождения (необязательно):<br>
                <input type="time" id="btime" name="btime" step="1" placeholder="чч:мм:сс">
            </label>
            <div class="note">Если время рождения не знаете или не хотите указывать — оставьте поле пустым.</div>
            <button type="submit">Показать календарь юбилеев</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

if (!$birthDateTime) {
    $birthDateTime = clone $birthDate;
}

$now = new DateTime('now');

$daysLived = (int)floor(($now->getTimestamp() - $birthDateTime->getTimestamp()) / 86400);
$hoursLived = (int)floor(($now->getTimestamp() - $birthDateTime->getTimestamp()) / 3600);

$jubileeDays = [12000, 13000, 20000, 25000, 30000, 40000];
$jubileeHours = [282000, 290000, 300000, 350000, 400000];

$futureJubileesDays = array_filter($jubileeDays, fn($d) => $d > $daysLived);
$futureJubileesHours = $birthTimeRaw ? array_filter($jubileeHours, fn($h) => $h > $hoursLived) : [];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Календарь моих юбилеев</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; max-width: 720px; margin: 2em auto; padding: 1em; background-color: #f9fafb; color: #222; }
        h1 { color: #005f99; border-bottom: 2px solid #005f99; padding-bottom: 0.3em; }
        .jubilee { margin-top: 1.5em; padding: 1em; background: #e3f2fd; border-radius: 8px; }
        .jubilee h2 { margin-top: 0; color: #0d47a1; }
        .item { margin-bottom: 0.8em; }
        .highlight { font-weight: bold; color: #0b3d91; }
    </style>
</head>
<body>
<h1>Календарь моих юбилеев</h1>

<div class="jubilee">
    <h2>Дни жизни</h2>
    <div class="item">Сейчас мне <span class="highlight"><?= number_format($daysLived, 0, '.', ' ') ?></span> <?= plural_form($daysLived, ['день', 'дня', 'дней']) ?></div>

<?php
foreach ($futureJubileesDays as $jubileeDay) {
    $targetTimestamp = $birthDateTime->getTimestamp() + $jubileeDay * 86400;
    $targetDate = (new DateTime())->setTimestamp($targetTimestamp);
    $intervalSeconds = $targetTimestamp - $now->getTimestamp();
    $durationStr = format_duration($intervalSeconds);
    ?>
    <div class="item"><?= number_format($jubileeDay, 0, '.', ' ') ?> <?= plural_form($jubileeDay, ['день', 'дня', 'дней']) ?>
        будет через <?= $durationStr ?> <?= format_date_rus($targetDate, false) ?>
    </div>
    <?php
}
?>
</div>

<div class="jubilee">
    <h2>Часы жизни</h2>
    <div class="item">Сейчас мне <span class="highlight"><?= number_format($hoursLived, 0, '.', ' ') ?></span> <?= plural_form($hoursLived, ['час', 'часа', 'часов']) ?></div>

<?php
if ($birthTimeRaw):
    foreach ($futureJubileesHours as $jubileeHour) {
        $targetTimestamp = $birthDateTime->getTimestamp() + $jubileeHour * 3600;
        $targetDate = (new DateTime())->setTimestamp($targetTimestamp);
        $intervalSeconds = $targetTimestamp - $now->getTimestamp();
        $days = floor($intervalSeconds / 86400);
        $dateStr = format_date_rus($targetDate, true);
        ?>
        <div class="item"><?= number_format($jubileeHour, 0, '.', ' ') ?> <?= plural_form($jubileeHour, ['час', 'часа', 'часов']) ?>
            будет через <?= $days ?> <?= plural_form($days, ['день', 'дня', 'дней']) ?> <?= $dateStr ?>
        </div>
        <?php
    }
else: ?>
    <div class="item">Время рождения не указано — часы жизни не рассчитываются</div>
<?php endif; ?>

<hr>
<div style="font-size:0.9em; color:#555;">
    <strong>Постоянная ссылка на календарь:</strong><br>
    <code><?= htmlspecialchars("http://" . ($_SERVER['HTTP_HOST'] ?? "localhost:8080") . $_SERVER['SCRIPT_NAME']) ?>?bday=<?= htmlspecialchars($birthDate->format('Y-m-d')) ?><?= $birthTimeRaw ? "&btime=" . htmlspecialchars($birthTimeRaw) : "" ?></code>
</div>
</body>
</html>
EOF

EXPOSE 8080

CMD [ "php", "-S", "0.0.0.0:8080", "-t", "/app" ]
