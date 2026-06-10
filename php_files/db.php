<?php
$host = "localhost"; //sunucu adresi
$db_name = "wtp_proje"; //db adi
$username = "admin"; // sansürlü
$password = "1234"; // sansürlü

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo "Veritabanı bağlantı hatası: " . $exception->getMessage();
    exit;
}
?>