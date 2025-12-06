<?php

require 'vendor/autoload.php';

use Predis\Client as PredisClient;

$r = new PredisClient([
                'scheme'   => 'tcp',
                'host'     => 'localhost',
                'port'     => 6379,
                'password' => '',
                'database' => 0,
            ]);

?>