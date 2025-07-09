<?php
$host = 'localhost';
$dbnaam = 'herkansing'; // Pas aan naar jouw database
$gebruiker = 'root';
$wachtwoord = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbnaam;charset=utf8", $gebruiker, $wachtwoord);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbinding met database mislukt: " . $e->getMessage());
}
?> 