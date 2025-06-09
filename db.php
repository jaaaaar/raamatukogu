<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Raamatukogu";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    die("Andmebaasi ühendus ebaõnnestus: " . $mysqli->connect_error);
}
?>
