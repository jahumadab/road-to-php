<?php
include 'bd.php';

$API_KEY = "TFEBGM5HTGTYG8NSDXMX7S5UY";
$base_url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/';

$data = null;
$message = null;
$locationInput = isset($_GET['location']) && trim($_GET['location']) !== ''
    ? trim($_GET['location'])
    : 'Santiago, CL';
$debug = [
    'from' => null,
    'cacheKey' => null,
    'requestUrlMasked' => null,
];

function normalize_city_key(string $location): string
{
    // Usa la parte de "ciudad" para la key
    $parts = explode(',', $location);
    $city = strtolower(trim($parts[0] ?? $location));
    $city = str_replace(' ', '_', $city);
    return "weather:v3:$city";
}

function vc_icon_to_asset(?string $icon): string
{
    // Mapea iconos de Visual Crossing => tus iconos locales
    $map = [
        'clear-day' => 'icon-sunny.webp',
        'clear-night' => 'icon-sunny.webp',
        'partly-cloudy-day' => 'icon-partly-cloudy.webp',
        'partly-cloudy-night' => 'icon-partly-cloudy.webp',
        'cloudy' => 'icon-overcast.webp',
        'overcast' => 'icon-overcast.webp',

        'rain' => 'icon-rain.webp',
        'showers-day' => 'icon-rain.webp',
        'showers-night' => 'icon-rain.webp',
        'drizzle' => 'icon-drizzle.webp',
        'snow' => 'icon-snow.webp',

        'thunder-rain' => 'icon-storm.webp',
        'thunder-showers-day' => 'icon-storm.webp',
        'thunder-showers-night' => 'icon-storm.webp',

        'fog' => 'icon-fog.webp',
        'wind' => 'icon-overcast.webp',
    ];

    $file = $map[$icon ?? ''] ?? 'icon-overcast.webp';
    return "./assets/images/" . $file;
}

function slim_response(array $api): array
{
    $days = array_slice($api['days'] ?? [], 0, 7);

    return [
        'queryCost' => $api['queryCost'] ?? null,
        'latitude' => $api['latitude'] ?? null,
        'longitude' => $api['longitude'] ?? null,
        'resolvedAddress' => $api['resolvedAddress'] ?? null,
        'address' => $api['address'] ?? null,
        'timezone' => $api['timezone'] ?? null,
        'tzoffset' => $api['tzoffset'] ?? null,
        'description' => $api['description'] ?? null,
        'currentConditions' => $api['currentConditions'] ?? [],
        'days' => $days, // cada day puede venir con hours[24]
    ];
}

function safe(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}


// Si el usuario no puso país, tú podrías agregar ",CL" automáticamente si quieres.
// Ej: si no contiene coma => $locationInput .= ",CL";

$cacheKey = normalize_city_key($locationInput);
$debug['cacheKey'] = $cacheKey;

if ($r->exists($cacheKey)) {
    $weatherData = $r->get($cacheKey);
    $data = json_decode($weatherData, true);
    $message = "Data retrieved from cache.";
    $debug['from'] = 'cache';
} else {
    $startDate = date('Y-m-d');
$endDate   = date('Y-m-d', strtotime('+6 days')); // 7 días contando hoy
$locationParam = urlencode($locationInput);

$url = $base_url . $locationParam . '/' . $startDate . '/' . $endDate .
    '/?key=' . $API_KEY .
    '&contentType=json' .
    '&unitGroup=metric' .
    '&include=days,hours,current';

    // URL para mostrar en UI sin exponer API KEY
    $debug['requestUrlMasked'] = str_replace($API_KEY, '***', $url);

    $response = @file_get_contents($url);
    if ($response === false) {
        $data = null;
        $message = "Error retrieving data from API.";
    } else {
        $api = json_decode($response, true);

        if (!is_array($api) || empty($api)) {
            http_response_code(404);
            $data = null;
            $message = "No data found for the specified location.";
        } else {
            $slim = slim_response($api);

            // Cache 1 hora
            $r->set($cacheKey, json_encode($slim, JSON_UNESCAPED_UNICODE), 'EX', 3600);

            $data = $slim;
            $message = "Data retrieved from API.";
            $debug['from'] = 'api';
        }
    }
}


// Valores por defecto (cuando no hay búsqueda todavía)
$place = $data['resolvedAddress'] ?? 'Berlin, Germany';
$day0 = $data['days'][0] ?? null;
$dateText = $day0 ? date('l, M j, Y', strtotime($day0['datetime'])) : 'Tuesday, Aug 5, 2025';

$current = $data['currentConditions'] ?? [];
$temp = $current['temp'] ?? 20;
$feelslike = $current['feelslike'] ?? ($day0['feelslike'] ?? $temp);
$humidity = $current['humidity'] ?? ($day0['humidity'] ?? 46);
$wind = $current['windspeed'] ?? ($day0['windspeed'] ?? 9);
$precip = $current['precip'] ?? ($day0['precip'] ?? 0);

$icon = $current['icon'] ?? ($day0['icon'] ?? 'clear-day');
$iconAsset = vc_icon_to_asset($icon);

$daily = $data['days'] ?? [];

$selectedDay = isset($_GET['day']) ? (int) $_GET['day'] : 0;
$selectedDay = max(0, min(6, $selectedDay));

$dayForHourly = $daily[$selectedDay] ?? ($daily[0] ?? null);
$hourly = $dayForHourly['hours'] ?? [];

$timezone = $data['timezone'] ?? 'UTC';
$tz = new DateTimeZone($timezone);
$now = new DateTime('now', $tz);

$baseDate = $dayForHourly['datetime'] ?? $now->format('Y-m-d');

// “Hoy” según timezone del lugar consultado (no según tu PC/servidor)
$isToday = ($baseDate === $now->format('Y-m-d'));

// Cortar desde la hora actual (redondeando hacia abajo a la hora)
$nowHour = (clone $now)->setTime((int)$now->format('H'), 0, 0);

$hourlyFiltered = [];
foreach ($hourly as $h) {
    $t = $h['datetime'] ?? '00:00:00';

    // Si VC trae "datetime" como "HH:MM:SS", armamos fecha+hora en timezone correcto
    $dt = new DateTime($baseDate . ' ' . $t, $tz);

    if ($isToday && $dt < $nowHour) {
        continue; // saltar horas pasadas
    }

    $hourlyFiltered[] = $h;
}

// (Opcional) Si por alguna razón quedó vacío, no rompas: deja al menos el siguiente item
if ($isToday && empty($hourlyFiltered) && !empty($hourly)) {
    $hourlyFiltered = array_slice($hourly, -1);
}





?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>El climapp</title>
    <link rel="stylesheet" href="./css/style.css" />
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>



    <main id="main" class="page">
        <h1 class="title">Cómo está el cielo ahora ?</h1>

        <form class="search" action="" method="get" role="search" aria-label="Buscar una ubicación">
            <label class="sr-only" for="q">Buscar un lugar</label>

            <div class="search__field">
                <img class="search__icon" src="./assets/images/icon-search.svg" alt="" aria-hidden="true" />
                <input id="q" name="location" type="search" placeholder="Buscar un lugar..." autocomplete="off"
                    value="<?= safe($locationInput) ?>" />
            </div>

            <button class="btn btn--primary" type="submit">Buscar</button>
        </form>

        <?php if ($message): ?>
            <p class="notice"><?= safe($message) ?></p>
        <?php endif; ?>

        <section class="layout" aria-label="Weather dashboard">
            <!-- LEFT -->
            <div class="left">
                <article class="card card--today" aria-label="Current weather">
                    <div class="today">
                        <div class="today__meta">
                            <h2 class="today__place"><?= safe($place) ?></h2>
                            <p class="today__date"><?= safe($dateText) ?></p>
                        </div>

                        <div class="today__now" aria-label="Temperature and condition">
                            <img class="today__icon" src="<?= safe($iconAsset) ?>"
                                alt="<?= safe((string) ($day0['conditions'] ?? '')) ?>" width="64" height="64" />
                            <p class="today__temp">
                                <span class="today__temp-value"><?= safe((string) round($temp)) ?></span><span
                                    class="today__deg">°</span>
                            </p>
                        </div>
                    </div>
                </article>

                <section class="stats" aria-label="Weather details">
                    <article class="card card--stat">
                        <h3 class="stat__label">Sensación</h3>
                        <p class="stat__value"><?= safe((string) round($feelslike)) ?>°</p>
                    </article>

                    <article class="card card--stat">
                        <h3 class="stat__label">Humedad</h3>
                        <p class="stat__value"><?= safe((string) round($humidity)) ?>%</p>
                    </article>

                    <article class="card card--stat">
                        <h3 class="stat__label">Viento</h3>
                        <p class="stat__value"><?= safe((string) round($wind)) ?> km/h</p>
                    </article>

                    <article class="card card--stat">
                        <h3 class="stat__label">Precipitación</h3>
                        <p class="stat__value"><?= safe((string) $precip) ?> mm</p>
                    </article>
                </section>

                <section class="daily" aria-label="Daily forecast">
                    <h2 class="section-title">Pronóstico diario</h2>

                    <div class="daily__grid" role="list">
                        <?php if (!empty($daily)): ?>
                            <?php foreach (array_slice($daily, 0, 7) as $idx => $d): ?>
                                <?php
                                $dayName = date('D', strtotime($d['datetime']));
                                $max = round($d['tempmax'] ?? 0);
                                $min = round($d['tempmin'] ?? 0);
                                $dIcon = vc_icon_to_asset($d['icon'] ?? null);
                                $dAlt = $d['conditions'] ?? 'Forecast';

                                $isActive = ($idx === $selectedDay);
                                $qs = http_build_query([
                                    'location' => $locationInput,
                                    'day' => $idx
                                ]);
                                ?>

                                <a class="card card--day <?= $isActive ? 'is-active' : '' ?>" href="?<?= safe($qs) ?>"
                                    role="listitem" aria-label="<?= safe($dayName) ?> forecast">
                                    <p class="day__name"><?= safe($dayName) ?></p>
                                    <img class="day__icon" src="<?= safe($dIcon) ?>" alt="<?= safe($dAlt) ?>" />
                                    <p class="day__temps"><span><?= $max ?>°</span><span><?= $min ?>°</span></p>
                                </a>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <?php for ($i = 0; $i < 7; $i++): ?>
                                <div class="card card--day" role="listitem" aria-label="Forecast placeholder">
                                    <p class="day__name">---</p>
                                    <img class="day__icon" src="./assets/images/icon-overcast.webp" alt="Forecast" />
                                    <p class="day__temps"><span>--°</span><span>--°</span></p>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>

                </section>

<!--                 <?php if ($data): ?>
                    <section class="card debug">
                        <h2 class="debug__title">Query / Response</h2>
                        <div class="debug__grid">
                            <div><span>queryCost</span><strong><?= safe((string) ($data['queryCost'] ?? '-')) ?></strong>
                            </div>
                            <div><span>latitude</span><strong><?= safe((string) ($data['latitude'] ?? '-')) ?></strong>
                            </div>
                            <div><span>longitude</span><strong><?= safe((string) ($data['longitude'] ?? '-')) ?></strong>
                            </div>
                            <div><span>timezone</span><strong><?= safe((string) ($data['timezone'] ?? '-')) ?></strong>
                            </div>
                        </div>

                        <?php if (!empty($data['description'])): ?>
                            <p class="debug__desc"><?= safe($data['description']) ?></p>
                        <?php endif; ?>

                        <details class="debug__details">
                            <summary>Show JSON (trimmed)</summary>
                            <pre><?= safe(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </details>

                        <?php if ($debug['requestUrlMasked']): ?>
                            <details class="debug__details">
                                <summary>Show request URL (masked)</summary>
                                <pre><?= safe($debug['requestUrlMasked']) ?></pre>
                            </details>
                        <?php endif; ?>
                    </section>
                <?php endif; ?> -->
            </div>

            <!-- RIGHT -->
            <aside class="right" aria-label="Hourly forecast">
                <section class="card card--hourly">
                    <header class="hourly__head">
                        <h2 class="hourly__title">Pronóstico por hora</h2>

                        <div class="cselect" data-cselect>
  <button class="cselect__btn" type="button" aria-haspopup="listbox" aria-expanded="false">
    <span class="cselect__value"><?= safe(date('l', strtotime($dayForHourly['datetime'] ?? 'now'))) ?></span>
    <img class="cselect__icon" src="./assets/images/icon-dropdown.svg" alt="" aria-hidden="true" />
  </button>

  <ul class="cselect__list" role="listbox">
    <?php foreach (array_slice($daily, 0, 7) as $i => $d): ?>
      <?php
        $label = date('l', strtotime($d['datetime']));
        $qs = http_build_query(['location' => $locationInput, 'day' => $i]);
        $href = "?" . $qs;
      ?>
      <li role="option" aria-selected="<?= $i === $selectedDay ? 'true' : 'false' ?>">
        <a class="cselect__opt <?= $i === $selectedDay ? 'is-selected' : '' ?>" href="<?= safe($href) ?>">
          <?= safe($label) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

                    </header>

                    <ul class="hourly__list" aria-label="Hours">
                        <?php if (!empty($hourlyFiltered)): ?>
                            <?php foreach ($hourlyFiltered  as $h): ?>
                                <?php
                                $baseDate = $dayForHourly['datetime'] ?? date('Y-m-d');
                                $time = date('g A', strtotime($baseDate . ' ' . ($h['datetime'] ?? '00:00:00')));
                                $htemp = round($h['temp'] ?? 0);
                                $hIcon = vc_icon_to_asset($h['icon'] ?? null);
                                $hAlt = $h['conditions'] ?? 'Hourly';
                                ?>
                                <li class="hour">
                                    <div class="hour__left">
                                        <img src="<?= safe($hIcon) ?>" alt="<?= safe($hAlt) ?>" />
                                        <span class="hour__time"><?= safe($time) ?></span>
                                    </div>
                                    <span class="hour__temp"><?= $htemp ?>°</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="hour hour--empty">
                                <span>No hourly data for this day.</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
            </aside>
        </section>
    </main>

    <script>
  (function () {
    const root = document.querySelector("[data-cselect]");
    if (!root) return;

    const btn = root.querySelector(".cselect__btn");
    const list = root.querySelector(".cselect__list");

    function close() {
      root.classList.remove("is-open");
      btn.setAttribute("aria-expanded", "false");
    }

    btn.addEventListener("click", () => {
      const open = root.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", String(open));
    });

    document.addEventListener("click", (e) => {
      if (!root.contains(e.target)) close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") close();
    });
  })();
</script>

</body>

</html>
