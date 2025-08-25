<?php
// Включаем вывод всех ошибок PHP для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверяем, что расширение Intl доступно
if (!extension_loaded('intl')) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Ошибка</title><style>body { font-family: sans-serif; text-align: center; margin-top: 50px; }</style></head><body>";
    echo "<h1>Ошибка сервера</h1>";
    echo "<p>Расширение PHP 'intl', необходимое для работы этого приложения, не установлено или не включено на вашем хостинге. Пожалуйста, обратитесь в техподдержку с этой информацией.</p>";
    echo "<p>Проблема: <strong>Расширение 'intl' не найдено.</strong></p>";
    echo "</body></html>";
    exit;
}

// Проверяем наличие даты рождения в URL
if (!isset($_GET['birthdate']) || empty($_GET['birthdate'])) {
    header("Location: index.php");
    exit;
}

// Получаем данные из GET-параметров
$birthdate_str = $_GET['birthdate'];
$birthtime_str = isset($_GET['birthtime']) && !empty($_GET['birthtime']) ? $_GET['birthtime'] : '00:00:00';
$timezone = isset($_GET['timezone']) && !empty($_GET['timezone']) ? $_GET['timezone'] : date_default_timezone_get();

// Устанавливаем часовой пояс для всего скрипта
date_default_timezone_set($timezone);

// Создаем объекты DateTime с учетом часового пояса пользователя
try {
    $birth_datetime = new DateTimeImmutable($birthdate_str . ' ' . $birthtime_str, new DateTimeZone($timezone));
    $now_datetime = new DateTimeImmutable('now', new DateTimeZone($timezone));
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
    exit;
}

// Используем IntDateFormatter для локализации
$dateFormatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::FULL, IntlDateFormatter::NONE, $timezone, IntlDateFormatter::GREGORIAN, 'd MMMM YYYY');
$timeFormatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::NONE, IntlDateFormatter::SHORT, $timezone);

/**
 * Функция для форматирования разницы во времени
 * @param DateTimeImmutable $from
 * @param DateTimeImmutable $to
 * @return string
 */
function format_diff(DateTimeImmutable $from, DateTimeImmutable $to): string {
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
    return implode(' ', $parts);
}

/**
 * Функция склонения слов (для красивого вывода)
 * @param int $n
 * @param array $words
 * @return string
 */
function declension(int $n, array $words): string {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $words[2];
    if ($n1 > 1 && $n1 < 5) return $words[1];
    if ($n1 == 1) return $words[0];
    return $words[2];
}

/**
 * Генерирует массив ближайших юбилеев
 * @param int $current_count
 * @param int $milestone_step
 * @param int $count
 * @return array
 */
function generateAnniversaries(int $current_count, int $milestone_step, int $count): array {
    $anniversaries = [];
    $milestone = floor($current_count / $milestone_step) * $milestone_step;
    for ($i = 0; $i < $count; $i++) {
        $milestone += $milestone_step;
        if ($milestone > $current_count) {
            $anniversaries[] = $milestone;
        }
    }
    return $anniversaries;
}

// Вычисляем текущее количество дней и часов
$current_days = $now_datetime->diff($birth_datetime)->days;
$current_hours = floor($now_datetime->getTimestamp() / 3600) - floor($birth_datetime->getTimestamp() / 3600);

// Генерируем ближайшие юбилеи (100-дневные/часовые)
$day_anniversaries_100 = generateAnniversaries($current_days, 100, 3);
$hour_anniversaries_100 = generateAnniversaries($current_hours, 100, 3);

// Генерируем далёкие юбилеи (1000-дневные/часовые)
$day_anniversaries_1000 = generateAnniversaries($current_days, 1000, 3);
$hour_anniversaries_1000 = generateAnniversaries($current_hours, 1000, 3);

// Объединяем и сортируем списки, оставляя уникальные значения
$day_anniversaries = array_unique(array_merge($day_anniversaries_100, $day_anniversaries_1000));
sort($day_anniversaries);

$hour_anniversaries = array_unique(array_merge($hour_anniversaries_100, $hour_anniversaries_1000));
sort($hour_anniversaries);
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
        h1, h2 { color: #007BFF; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0; }
        h2 { margin-top: 20px; }
        .current-stats { background-color: #e9ecef; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 1.1em; }
        .anniversary-list { margin-top: 20px; }
        .anniversary-item { border-bottom: 1px dashed #ccc; padding: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .anniversary-item:last-child { border-bottom: none; }
        .anniversary-value { font-weight: bold; color: #333; }
        .anniversary-date { color: #666; font-style: italic; font-size: 0.9em; text-align: right; }
        .soon { color: #dc3545; font-weight: bold; }
        .share-link { margin-top: 30px; text-align: center; }
        .share-link a { display: inline-block; padding: 10px 20px; background-color: #28a745; color: #fff; text-decoration: none; border-radius: 4px; transition: background-color 0.3s; }
        .share-link a:hover { background-color: #218838; }
        @media (max-width: 600px) {
            .anniversary-item { flex-direction: column; align-items: flex-start; }
            .anniversary-date { text-align: left; margin-top: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Календарь моих юбилеев</h1>
        <div class="current-stats">
            <p>Сейчас мне <strong><?php echo number_format($current_days, 0, ',', ' '); ?></strong> <?php echo declension($current_days, ['день', 'дня', 'дней']); ?></p>
            <p>Сейчас мне <strong><?php echo number_format($current_hours, 0, ',', ' '); ?></strong> <?php echo declension($current_hours, ['час', 'часа', 'часов']); ?></p>
        </div>

        <h2>Юбилеи в днях</h2>
        <div class="anniversary-list">
            <?php foreach ($day_anniversaries as $milestone) :
                $anniversary_datetime = $birth_datetime->modify("+{$milestone} days");
                $days_to_go = $anniversary_datetime->diff($now_datetime)->days;
                $diff_text = format_diff($now_datetime, $anniversary_datetime);
                $is_soon = $days_to_go < 200;
            ?>
                <div class="anniversary-item">
                    <span class="anniversary-value"><strong><?php echo number_format($milestone, 0, ',', ' '); ?></strong> <?php echo declension($milestone, ['день', 'дня', 'дней']); ?></span>
                    <span class="anniversary-date <?php if ($is_soon) echo 'soon'; ?>">
                        <?php echo $is_soon ? 'будет скоро' : 'будет'; ?> &ndash; <strong><?php echo $dateFormatter->format($anniversary_datetime); ?></strong>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Юбилеи в часах</h2>
        <div class="anniversary-list">
            <?php foreach ($hour_anniversaries as $milestone) :
                $anniversary_datetime = $birth_datetime->modify("+{$milestone} hours");
                $hours_to_go = floor($anniversary_datetime->getTimestamp() / 3600) - floor($now_datetime->getTimestamp() / 3600);
                $is_soon = $hours_to_go < 24 * 14; // 14 дней
                
                // Проверяем, что дата юбилея находится в будущем
                if ($anniversary_datetime > $now_datetime) :
                    $diff_text = format_diff($now_datetime, $anniversary_datetime);
            ?>
                <div class="anniversary-item">
                    <span class="anniversary-value"><strong><?php echo number_format($milestone, 0, ',', ' '); ?></strong> <?php echo declension($milestone, ['час', 'часа', 'часов']); ?></span>
                    <span class="anniversary-date <?php if ($is_soon) echo 'soon'; ?>">
                        <?php echo $is_soon ? 'будет скоро' : 'будет'; ?> &ndash; <strong><?php echo $dateFormatter->format($anniversary_datetime); ?></strong> <?php if ($_GET['birthtime'] != '') echo 'в ' . $timeFormatter->format($anniversary_datetime); ?>
                    </span>
                </div>
            <?php endif; endforeach; ?>
        </div>
        
        <div class="share-link">
            <p>Постоянная ссылка:</p>
            <a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">Мои юбилеи</a>
        </div>
    </div>
</body>
</html>