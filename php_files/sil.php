<?php
require_once 'db.php';
session_start();

// güvenlik kontrolü
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// url den gelen bir id kontrolü
if(isset($_GET['id'])) {
    $gorev_id = $_GET['id'];
    $kullanici_id = $_SESSION['user_id'];

    // Kullanıcı sadece KENDİNE ait görevi silebilsin diye iki parametreyi de kontrol ediyoruz
    $sorgu = $db->prepare("DELETE FROM gorevler WHERE id = ? AND kullanici_id = ?");
    $sorgu->execute([$gorev_id, $kullanici_id]);
}

// silme sonrasında ana sayfaya dön
header("Location: index.php");
exit;
?>