<?php
$apiKey = "0210af2ffc905dc43a4828828d93f38e";

$city = isset($_GET['city']) && trim($_GET['city']) !== '' ? trim($_GET['city']) : 'Shakopee';
$units = isset($_GET['units']) && $_GET['units'] === 'imperial' ? 'imperial' : 'metric';

$tempUnit = $units === 'metric' ? 'C' : 'F';
$speedUnit = $units === 'metric' ? 'm/s' : 'mph';

function windDirection($deg) {
    $directions = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $index = (int)(($deg / 22.5) + 0.5) % 16;
    return $directions[$index];
}

function formatTime($timestamp, $timezoneOffset, $format = 'g:i a') {
    return gmdate($format, $timestamp + $timezoneOffset);
}

$apiUrl = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city)
    . "&lang=en&units=" . $units . "&appid=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$errorMessage = '';
if ($response === false) {
    $errorMessage = 'Request error: ' . curl_error($ch);
}
curl_close($ch);

$data = $response ? json_decode($response) : null;
if ($data && isset($data->cod) && $data->cod != 200) {
    $errorMessage = 'API error: ' . ($data->message ?? 'Unable to retrieve data');
}

$currentTime = time();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Weather Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f5; color: #333; margin: 0; padding: 20px; }
        .report-container { max-width: 640px; margin: auto; background: #fff; border: 1px solid #d8dce0; border-radius: 10px; padding: 24px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .report-container h2 { margin-top: 0; }
        .weather-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .weather-icon { width: 90px; height: 90px; }
        .weather-forecast { font-size: 2rem; font-weight: bold; margin: 12px 0; }
        .weather-details { display: grid; grid-template-columns: repeat(2, minmax(140px, 1fr)); gap: 12px; margin: 16px 0; }
        .weather-details div { background: #f7f9fb; border-radius: 8px; padding: 12px; }
        .time, .error { margin-top: 10px; }
        form { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        input, select, button { padding: 10px 12px; border: 1px solid #bfc9d3; border-radius: 6px; font-size: 1rem; }
        button { background: #1976d2; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #145ea8; }
        .error { color: #b00020; }
        .status { color: #555; }
    </style>
</head>
<body>
    <div class="report-container">
        <form method="get" action="">
            <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Enter city name">
            <select name="units">
                <option value="metric" <?php echo $units === 'metric' ? 'selected' : ''; ?>>Metric (C)</option>
                <option value="imperial" <?php echo $units === 'imperial' ? 'selected' : ''; ?>>Imperial (F)</option>
            </select>
            <button type="submit">Update</button>
        </form>

        <?php if ($errorMessage): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php elseif ($data): ?>
            <div class="weather-header">
                <h2><?php echo htmlspecialchars($data->name . ', ' . $data->sys->country); ?></h2>
                <img src="https://openweathermap.org/img/wn/<?php echo htmlspecialchars($data->weather[0]->icon); ?>@2x.png"
                     alt="<?php echo htmlspecialchars($data->weather[0]->description); ?>"
                     class="weather-icon">
            </div>

            <div class="time">
                <div><?php echo date("l g:i a", $currentTime); ?></div>
                <div><?php echo date("jS F, Y", $currentTime); ?></div>
                <div class="status"><?php echo ucwords(htmlspecialchars($data->weather[0]->description)); ?></div>
            </div>

            <div class="weather-forecast">
                <?php echo round($data->main->temp); ?>&deg;<?php echo $tempUnit; ?>
                <span style="font-size: 0.7em; color: #555;">(feels like <?php echo round($data->main->feels_like); ?>&deg;<?php echo $tempUnit; ?>)</span>
            </div>

            <div class="weather-details">
                <div><strong>Min / Max</strong><br><?php echo round($data->main->temp_min); ?>&deg;<?php echo $tempUnit; ?> / <?php echo round($data->main->temp_max); ?>&deg;<?php echo $tempUnit; ?></div>
                <div><strong>Humidity</strong><br><?php echo htmlspecialchars($data->main->humidity); ?>%</div>
                <div><strong>Pressure</strong><br><?php echo htmlspecialchars($data->main->pressure); ?> hPa</div>
                <div><strong>Clouds</strong><br><?php echo htmlspecialchars($data->clouds->all); ?>%</div>
                <div><strong>Wind</strong><br><?php echo htmlspecialchars($data->wind->speed); ?> <?php echo $speedUnit; ?> <?php echo windDirection($data->wind->deg); ?></div>
                <div><strong>Coordinates</strong><br><?php echo htmlspecialchars($data->coord->lat); ?>, <?php echo htmlspecialchars($data->coord->lon); ?></div>
            </div>

            <div class="weather-details">
                <div><strong>Sunrise</strong><br><?php echo formatTime($data->sys->sunrise, $data->timezone); ?></div>
                <div><strong>Sunset</strong><br><?php echo formatTime($data->sys->sunset, $data->timezone); ?></div>
                <div><strong>Timezone</strong><br>UTC <?php echo ($data->timezone >= 0 ? '+' : '') . ($data->timezone / 3600); ?></div>
            </div>
        <?php else: ?>
            <div class="error">Weather data is not available.</div>
        <?php endif; ?>
    </div>
</body>
</html>