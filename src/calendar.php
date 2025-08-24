<?php
// Проверяем наличие даты рождения в URL
if (!isset($_GET['birthdate']) || empty($_GET['birthdate'])) {
    header("Location: index.php");
    exit;
}

// Получаем дату и время рождения из GET-параметров
$birthdate_str = $_GET['birthdate'];
$birthtime_str = isset($_GET['birthtime']) && !empty($_GET['birthtime']) ? $_GET['birthtime'] : '00:00';

$birth_datetime = new DateTime($birthdate_str . ' ' . $birthtime_str);
$now_datetime = new DateTime();

// Вычисляем текущее количество дней и часов
$current_days = $now_datetime->diff($birth_datetime)->days;
$current_hours = floor($now_datetime->getTimestamp() / 3600) - floor($birth_datetime->getTimestamp() / 3600);

// Функция для форматирования разницы во времени
function format_diff(DateTime $from, DateTime $to) {
    $diff = $to->diff($from);
    $parts = [];
    if ($diff->y > 0) {
        $parts[] = $diff->y . ' ' . declension($diff->y, ['год', 'года', 'лет']);
    }
    if ($diff->m > 0) {
        $parts[] = $diff->m . ' ' . declension($diff->m, ['месяц', 'месяца', 'месяцев']);
    }
    if ($diff->d > 0) {
        $parts[] = $diff->d . ' ' . declension($diff->d, ['день', 'дня', 'дней']);
    }
    return empty($parts) ? 'сегодня' : implode(' ', $parts);
}

// Функция склонения слов (для красивого вывода)
function declension($n, $words) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $words[2];
    if ($n1 > 1 && $n1 < 5) return $words[1];
    if ($n1 == 1) return $words[0];
    return $words[2];
}

// Массивы для юбилеев
$day_anniversaries = [10000, 11000, 12000, 13000, 14000, 15000, 16000, 17000, 18000, 19000, 20000, 25000, 30000];
$hour_anniversaries = [100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000, 1000000];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь юбилеев</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 20px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1, h2 { color: #007BFF; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .current-stats { background-color: #e9ecef; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 1.1em; }
        .anniversary-list { margin-top: 20px; }
        .anniversary-item { border-bottom: 1px dashed #ccc; padding: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .anniversary-item:last-child { border-bottom: none; }
        .anniversary-value { font-weight: bold; color: #333; }
        .anniversary-date { color: #666; font-style: italic; font-size: 0.9em; }
        .share-link { margin-top: 30px; text-align: center; }
        .share-link a { display: inline-block; padding: 10px 20px; background-color: #28a745; color: #fff; text-decoration: none; border-radius: 4px; transition: background-color 0.3s; }
        .share-link a:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Календарь моих юбилеев</h1>
        <div class="current-stats">
            <p>Сейчас мне **<?php echo number_format($current_days, 0, ',', ' '); ?>** <?php echo declension($current_days, ['день', 'дня', 'дней']); ?></p>
            <p>Сейчас мне **<?php echo number_format($current_hours, 0, ',', ' '); ?>** <?php echo declension($current_hours, ['час', 'часа', 'часов']); ?></p>
        </div>

        <h2>Юбилеи в днях</h2>
        <div class="anniversary-list">
            <?php foreach ($day_anniversaries as $milestone) :
                if ($milestone > $current_days) :
                    $anniversary_datetime = clone $birth_datetime;
                    $anniversary_datetime->modify("+{$milestone} days");
                    $diff_text = format_diff($now_datetime, $anniversary_datetime);
            ?>
                <div class="anniversary-item">
                    <span class="anniversary-value">**<?php echo number_format($milestone, 0, ',', ' '); ?>** <?php echo declension($milestone, ['день', 'дня', 'дней']); ?></span>
                    <span class="anniversary-date">будет через <?php echo $diff_text; ?> &ndash; **<?php echo $anniversary_datetime->format('d F Y'); ?>**</span>
                </div>
            <?php endif; endforeach; ?>
        </div>

        <h2>Юбилеи в часах</h2>
        <div class="anniversary-list">
            <?php foreach ($hour_anniversaries as $milestone) :
                if ($milestone > $current_hours) :
                    $anniversary_datetime = clone $birth_datetime;
                    $anniversary_datetime->modify("+{$milestone} hours");
                    $diff_text = format_diff($now_datetime, $anniversary_datetime);
            ?>
                <div class="anniversary-item">
                    <span class="anniversary-value">**<?php echo number_format($milestone, 0, ',', ' '); ?>** <?php echo declension($milestone, ['час', 'часа', 'часов']); ?></span>
                    <span class="anniversary-date">будет через <?php echo $diff_text; ?> &ndash; **<?php echo $anniversary_datetime->format('d F Y'); ?>** <?php if ($birthtime_str != '00:00') echo 'в ' . $anniversary_datetime->format('H:i'); ?></span>
                </div>
            <?php endif; endforeach; ?>
        </div>
        
        <div class="share-link">
            <p>Поделитесь этой ссылкой с друзьями!</p>
            <a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">Поделиться</a>
        </div>
    </div>
</body>
</html>