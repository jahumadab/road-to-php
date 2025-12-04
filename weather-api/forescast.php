<?php
    $API_KEY = "TFEBGM5HTGTYG8NSDXMX7S5UY";
    $base_url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/';
    $data = null;


    if (isset($_GET['location'])) {
        $location = urlencode($_GET['location'] . ',CL');
        $currentDate = date('Y-m-d');
        
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

        /* $response = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        echo $response; */

        

            
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
    <h1>Clima Actual en <?= $data['resolvedAddress']?> </h1>
    <h2>Fecha: <?= $data['days'][0]['datetime'] ?> </h2>
    <h3> <?= $url?> </h3>
    <table>

        <tr>
        <th>Temperatura minima</th>
        <td><?=  $data['days'][0]['tempmin'] ?> °C</td>
        </tr>
        <tr>
        <th>Temperatura actual</th>
        <td><?=  $data['currentConditions']['temp'] ?> °C</td>
        </tr>
        <tr>
        <th>Temperatura maxima</th>
        <td><?=  $data['days'][0]['tempmax'] ?> °C</td>
        </tr>
    </table>
</body>
</html>