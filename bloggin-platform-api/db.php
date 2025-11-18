<?php
$servername = "localhost";
$username = "javier";
$password = "1234";
$dbname = "blog";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


?>