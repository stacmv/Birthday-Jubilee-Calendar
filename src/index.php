<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь юбилеев</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background-color: #fff; padding: 25px 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; box-sizing: border-box; }
        h1 { color: #333; margin-bottom: 20px; }
        form { display: flex; flex-direction: column; gap: 15px; }
        label { text-align: left; font-weight: bold; }
        input[type="date"], input[type="time"] { padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { padding: 10px 20px; border: none; border-radius: 4px; background-color: #007BFF; color: #fff; font-size: 16px; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Календарь юбилеев</h1>
        <form action="calendar.php" method="GET">
            <label for="birthdate">Дата рождения:</label>
            <input type="date" id="birthdate" name="birthdate" required>
            
            <label for="birthtime">Время рождения (необязательно):</label>
            <input type="time" id="birthtime" name="birthtime">
            
            <input type="hidden" id="timezone" name="timezone">
            
            <button type="submit">Сгенерировать календарь</button>
        </form>
    </div>

    <script>
        // Автоматически определяем и устанавливаем часовой пояс пользователя
        document.getElementById('timezone').value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    </script>
</body>
</html> 