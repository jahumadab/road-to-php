<?php

    include 'bd.php';


    $API_KEY = "TFEBGM5HTGTYG8NSDXMX7S5UY";
    $base_url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/';
    $data = null;


    if (isset($_GET['location'])) {

        $location = $_GET['location'];
        $currentDate = date('Y-m-d');

        list($city) = explode(',', $location);
        $city = strtolower(trim($city));
        $city = str_replace(' ', '_', $city);
        $key = "weather:$city";
        
        if( $r->exists($key) ) {
            $weatherData = $r->get($key);
            $data = json_decode($weatherData, true);
            $message =  "Data retrieved from cache.";
            
        }
        else {

        $url = $base_url . $location . '/' .$currentDate . 
         '/?key=' . $API_KEY .
         '&contentType=json' . '&unitGroup=metric' . '&include=current';

        $response = file_get_contents($url );
        
        $data = json_decode($response, true);

        if ($data == null) {
            http_response_code(404);
            echo json_encode(['error' => 'No data found for the specified location.']);
            exit;
        }

        list($city) = explode(',', $data['resolvedAddress']);
        $city = strtolower(trim($city));
        $city = str_replace(' ', '_', $city);
        $key = "weather:$city";


        $data = json_encode([
            'location' => $data['resolvedAddress'],
            'date' => $data['days'][0]['datetime'],
            'tempmin' => $data['days'][0]['tempmin'],
            'temp' => $data['currentConditions']['temp'],
            'tempmax' => $data['days'][0]['tempmax'],
        ], JSON_THROW_ON_ERROR);
        
        $r->set($key, $data, 'EX', 3600); // Expira en 1 hora

        $data = json_decode($data, true);

        $message = "Data retrieved from API";

        /* $response = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        echo $response; */
    }
        

            
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clima</title>
</head>
<body>
    <h1>Clima Actual en <?= $data['location']?> </h1>
    <h2>Fecha: <?= $data['date'] ?> </h2>
    <h3> <?= $message?> </h3>
    <table>

        <tr>
        <th>Temperatura minima</th>
        <td><?=  $data['tempmin'] ?> °C</td>
        </tr>
        <tr>
        <th>Temperatura actual</th>
        <td><?=  $data['temp'] ?> °C</td>
        </tr>
        <tr>
        <th>Temperatura maxima</th>
        <td><?=  $data['tempmax'] ?> °C</td>
        </tr>
    </table>
</body>
</html>