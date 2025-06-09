<?php
$host = "localhost";
$user = "raltmae";
$pass = "XrtumCyCGOUb0pq0";
$dbname = "raltmae";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    die("Andmebaasi Ć¼hendus ebaĆµnnestus: " . $mysqli->connect_error);
}
?>
