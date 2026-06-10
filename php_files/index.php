<?php
require_once 'db.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$kullanici_id = $_SESSION['user_id'];
$mesaj = "";
$profil_hata = "";
$profil_basari = "";
$sifre_hata = "";
$sifre_basari = "";

if(isset($_SESSION['flash_mesaj'])) { $mesaj = $_SESSION['flash_mesaj']; unset($_SESSION['flash_mesaj']); }
if(isset($_SESSION['profil_hata'])) { $profil_hata = $_SESSION['profil_hata']; unset($_SESSION['profil_hata']); }
if(isset($_SESSION['profil_basari'])) { $profil_basari = $_SESSION['profil_basari']; unset($_SESSION['profil_basari']); }
if(isset($_SESSION['sifre_hata'])) { $sifre_hata = $_SESSION['sifre_hata']; unset($_SESSION['sifre_hata']); }
if(isset($_SESSION['sifre_basari'])) { $sifre_basari = $_SESSION['sifre_basari']; unset($_SESSION['sifre_basari']); }

// kullanıcı adı güncelleme 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['profil_guncelle'])) {
    $yeni_kullanici = trim($_POST['yeni_kullanici_adi']);

    if(!empty($yeni_kullanici)) {
        // eski adı girip boşuna db meşgul edilmemesi için kullanıcı adı kontrolü
        if($yeni_kullanici === $_SESSION['kullanici_adi']) {
            $_SESSION['profil_hata'] = "Yeni kullanıcı adınız mevcut adınızla aynı olamaz!";
        } else {
            try {
                $guncelle = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ? WHERE id = ?");
                $guncelle->execute([$yeni_kullanici, $kullanici_id]);
                $_SESSION['kullanici_adi'] = $yeni_kullanici; 
                $_SESSION['profil_basari'] = "Kullanıcı adı başarıyla güncellendi.";
            } catch(PDOException $e) {
                $_SESSION['profil_hata'] = "Bu kullanıcı adı zaten alınmış.";
            }
        }
    }
    header("Location: index.php");
    exit;
}

// kullanılan şifre konrolüyle yeni şifre girişi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sifre_degistir'])) {
    $eski_sifre = $_POST['eski_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];

    if(!empty($eski_sifre) && !empty($yeni_sifre)) {
        // mevcut şifreyi dbden çekmek için
        $sorgu = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
        $sorgu->execute([$kullanici_id]);
        $kullanici_verisi = $sorgu->fetch(PDO::FETCH_ASSOC);

        // eski şifre doğru mu kontrolü için
        if(password_verify($eski_sifre, $kullanici_verisi['sifre'])) {
            
            // tekrar dbyi yormamak için mevcut olanla yeni istenen giriş aynı mı onun kontolü için
            if($eski_sifre === $yeni_sifre) {
                $_SESSION['sifre_hata'] = "Yeni şifreniz eskisiyle aynı olamaz!";
            } else {
                // db update
                $hashed_yeni_sifre = password_hash($yeni_sifre, PASSWORD_BCRYPT);
                $sifre_guncelle = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                $sifre_guncelle->execute([$hashed_yeni_sifre, $kullanici_id]);
                $_SESSION['sifre_basari'] = "Şifreniz başarıyla değiştirildi.";
            }

        } else {
            // şifre yanlışsa modalda çıkacak bildirim 
            $_SESSION['sifre_hata'] = "Mevcut şifrenizi yanlış girdiniz!";
        }
    }
    header("Location: index.php");
    exit;
}

// görev ekleme 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gorev_ekle'])) {
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $durum = $_POST['durum'];
    $oncelik = $_POST['oncelik'];

    if(!empty($baslik) && !empty($aciklama)) {
        $sorgu = $db->prepare("INSERT INTO gorevler (kullanici_id, baslik, aciklama, durum, oncelik) VALUES (?, ?, ?, ?, ?)");
        $sorgu->execute([$kullanici_id, $baslik, $aciklama, $durum, $oncelik]);
        $_SESSION['flash_mesaj'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Görev eklendi.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        header("Location: index.php");
        exit;
    }
}

// oluşturulan göreve katılımcı davet etme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['davet_et'])) {
    $gorev_id = $_POST['gorev_id'];
    $davet_email = trim($_POST['email']);
    $user_sorgu = $db->prepare("SELECT id FROM kullanicilar WHERE email = ?");
    $user_sorgu->execute([$davet_email]);
    $davet_edilen = $user_sorgu->fetch(PDO::FETCH_ASSOC);

    if ($davet_edilen) {
        $davet_edilen_id = $davet_edilen['id'];
        if ($davet_edilen_id == $kullanici_id) {
            $_SESSION['flash_mesaj'] = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>Kendinizi ortak olarak davet edemezsiniz.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $kontrol = $db->prepare("SELECT id FROM gorev_ortaklari WHERE gorev_id = ? AND kullanici_id = ?");
            $kontrol->execute([$gorev_id, $davet_edilen_id]);
            if ($kontrol->fetch()) {
                $_SESSION['flash_mesaj'] = "<div class='alert alert-info alert-dismissible fade show' role='alert'>Bu kullanıcı zaten davet edilmiş veya ortak.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $davet_ekle = $db->prepare("INSERT INTO gorev_ortaklari (gorev_id, kullanici_id, durum) VALUES (?, ?, 'Beklemede')");
                $davet_ekle->execute([$gorev_id, $davet_edilen_id]);
                $_SESSION['flash_mesaj'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Davet gönderildi.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    } else {
        $_SESSION['flash_mesaj'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Kullanıcı bulunamadı.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
    header("Location: index.php");
    exit;
}

// davetin onayı/reddi
if (isset($_GET['davet_id']) && isset($_GET['islem'])) {
    $d_id = $_GET['davet_id'];
    $islem = $_GET['islem'] == 'onayla' ? 'Onaylandı' : 'Reddedildi';
    $davet_guncelle = $db->prepare("UPDATE gorev_ortaklari SET durum = ? WHERE id = ? AND kullanici_id = ?");
    $davet_guncelle->execute([$islem, $d_id, $kullanici_id]);
    header("Location: index.php");
    exit;
}

$davet_sorgu = $db->prepare("SELECT go.id as davet_id, g.baslik, k.kullanici_adi FROM gorev_ortaklari go JOIN gorevler g ON go.gorev_id = g.id JOIN kullanicilar k ON g.kullanici_id = k.id WHERE go.kullanici_id = ? AND go.durum = 'Beklemede'");
$davet_sorgu->execute([$kullanici_id]);
$gelen_davetler = $davet_sorgu->fetchAll(PDO::FETCH_ASSOC);

$sorgu = $db->prepare("SELECT DISTINCT g.*, k.kullanici_adi as sahibi_adi FROM gorevler g JOIN kullanicilar k ON g.kullanici_id = k.id LEFT JOIN gorev_ortaklari go ON g.id = go.gorev_id WHERE g.kullanici_id = ? OR (go.kullanici_id = ? AND go.durum = 'Onaylandı') ORDER BY g.id DESC");
$sorgu->execute([$kullanici_id, $kullanici_id]);
$gorevler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İş Akışı ve Ortak Görev Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* karanlık mod */
        body.dark-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        .dark-mode .card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .form-control, .dark-mode .form-select { background-color: #2d2d2d !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .text-muted { color: #adb5bd !important; }
        .dark-mode .navbar { background-color: #000 !important; }
        .dark-mode .input-group-text { background-color: #3a3a3a !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #444 !important; }
        .dark-mode .list-group-item { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .bg-light { background-color: #252525 !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .form-control::placeholder { color: #888 !important; opacity: 1; }
        .form-control:focus, .form-select:focus, .btn:focus { box-shadow: none !important; border-color: #ced4da !important; }
        .dark-mode .form-control:focus, .dark-mode .form-select:focus { box-shadow: none !important; border-color: #555 !important; background-color: #333 !important; }
        .dark-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #333; color: #fff; }
        .dark-mode .dropdown-divider { border-top-color: #444; }
        .dark-mode .form-check-label { color: #e0e0e0 !important; }
        .dark-mode .modal-header { border-bottom-color: #444; }
        .dark-mode .modal-header.bg-light { background-color: #252525 !important; color: #fff !important; border-bottom-color: #444; }
        .dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .dark-mode .table { 
            --bs-table-color: #e0e0e0; --bs-table-bg: #1e1e1e; --bs-table-border-color: #333;
            --bs-table-striped-bg: #252525; --bs-table-striped-color: #e0e0e0;
            --bs-table-hover-bg: #2a2a2a; --bs-table-hover-color: #ffffff;
        }
        .dark-mode select option { background-color: #2d2d2d; color: #fff; }
        /* scroll bar değişimmi*/
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        .dark-mode ::-webkit-scrollbar-track { background: #1e1e1e; }
        .dark-mode ::-webkit-scrollbar-thumb { background: #444; }
        .dark-mode ::-webkit-scrollbar-thumb:hover { background: #555; }

        .avatar-group { display: flex; align-items: center; }
        .avatar-circle { 
            width: 32px; height: 32px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; 
            font-size: 13px; font-weight: bold; border: 2px solid #fff; margin-left: -8px; transition: all 0.2s ease; text-transform: uppercase; position: relative;
        }
        .avatar-circle:nth-child(1) { z-index: 10; } .avatar-circle:nth-child(2) { z-index: 9; } .avatar-circle:nth-child(3) { z-index: 8; }
        .avatar-circle:nth-child(4) { z-index: 7; } .avatar-circle:nth-child(5) { z-index: 6; }
        .avatar-circle:first-child { margin-left: 0; }
        .avatar-circle:hover { transform: translateY(-2px); z-index: 20 !important; }
        .avatar-circle-sm { width: 28px; height: 28px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        
        .avatar-add { background-color: transparent !important; color: #0d6efd; border: 2px dashed #0d6efd; cursor: pointer; }
        .avatar-add:hover { background-color: #0d6efd !important; color: white !important; border-style: solid; }
        .dark-mode .avatar-add { border-color: #4dabf7 !important; color: #4dabf7 !important; }
        .dark-mode .avatar-add:hover { background-color: #4dabf7 !important; color: #121212 !important; border-style: solid; }
        
        .three-dots-btn { background: none; border: none; padding: 0; display: flex; align-items: center; color: rgba(255,255,255,0.7); transition: color 0.2s; }
        .three-dots-btn:hover { color: #fff; }

        .password-group { transition: border-color 0.15s ease-in-out; border-radius: 0.375rem; }
        .password-group .form-control { border-right: none; box-shadow: none !important; }
        .password-group .btn { border-left: none; background-color: transparent; border-color: #dee2e6; color: #6c757d; font-size: 0.9rem; box-shadow: none !important; }
        .password-group .btn:hover { background-color: #f8f9fa; }
        
        .password-group:focus-within { border-color: #ced4da; box-shadow: none !important; }
        .password-group:focus-within .form-control, .password-group:focus-within .btn { border-color: #ced4da !important; }
        
        .dark-mode .password-group .form-control { border-color: #444 !important; }
        .dark-mode .password-group .btn { border-color: #444 !important; color: #adb5bd; }
        .dark-mode .password-group .btn:hover { background-color: #333 !important; color: #fff; }
        .dark-mode .password-group:focus-within { border-color: #555 !important; box-shadow: none !important; }
        .dark-mode .password-group:focus-within .form-control, .dark-mode .password-group:focus-within .btn { border-color: #555 !important; }
    </style>
    <script>
        if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark-mode'); }
    </script>
</head>
<body class="bg-light">
<script>
    if (localStorage.getItem('theme') === 'dark') { document.body.classList.add('dark-mode'); }
</script>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Ana Panel</a>
        <div class="navbar-nav ms-auto align-items-center">
            <div class="dropdown me-3">
                <button class="three-dots-btn" type="button" id="settingsMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="settingsMenu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">Profil Ayarları</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="px-3 py-1">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle">
                            <label class="form-check-label small" for="darkModeToggle">Karanlık Mod</label>
                        </div>
                    </li>
                </ul>
            </div>
            <span class="navbar-text me-3 text-white">Hoş geldin, <strong><?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?></strong></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Güvenli Çıkış</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php echo $mesaj; ?>

    <?php if(!empty($gelen_davetler)): ?>
        <div class="card border-warning mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark"><strong>Görev Ortaklığı Davetleriniz Var</strong></div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach($gelen_davetler as $davet): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong><?php echo htmlspecialchars($davet['kullanici_adi']); ?></strong>, sizi <strong><?php echo htmlspecialchars($davet['baslik']); ?></strong> görevine ortak olmaya davet etti.</span>
                            <div>
                                <a href="index.php?davet_id=<?php echo $davet['davet_id']; ?>&islem=onayla" class="btn btn-success btn-sm me-2">Onayla</a>
                                <a href="index.php?davet_id=<?php echo $davet['davet_id']; ?>&islem=reddet" class="btn btn-danger btn-sm">Reddet</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">Yeni Görev Ekle</h5></div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Görev Başlığı</label>
                            <input type="text" name="baslik" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3" style="resize: none;" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select name="durum" class="form-select">
                                <option value="Beklemede">Beklemede</option>
                                <option value="Devam Ediyor">Devam Ediyor</option>
                                <option value="Tamamlandı">Tamamlandı</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Öncelik</label>
                            <select name="oncelik" class="form-select">
                                <option value="Düşük">Düşük</option>
                                <option value="Orta" selected>Orta</option>
                                <option value="Yüksek">Yüksek</option>
                            </select>
                        </div>
                        <button type="submit" name="gorev_ekle" class="btn btn-primary w-100">Görevi Kaydet</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white"><h5 class="mb-0">Mevcut Görevleriniz</h5></div>
                <div class="card-body">
                    <?php if(empty($gorevler)): ?>
                        <p class="text-muted mb-0">Henüz bir görev yok.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Başlık</th>
                                        <th>Açıklama</th>
                                        <th>Durum</th>
                                        <th>Öncelik</th>
                                        <th>Katılımcılar</th>
                                        <th class="text-center">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($gorevler as $gorev): ?>
                                        <tr>
                                            <td class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($gorev['baslik']); ?>">
                                                <strong><?php echo htmlspecialchars($gorev['baslik']); ?></strong>
                                            </td>
                                            
                                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($gorev['aciklama']); ?>">
                                                <small class="text-muted"><?php echo htmlspecialchars($gorev['aciklama']); ?></small>
                                            </td>
                                            
                                            <td>
                                                <?php 
                                                if($gorev['durum'] == 'Beklemede') echo '<span class="badge bg-warning text-dark">Beklemede</span>';
                                                elseif($gorev['durum'] == 'Devam Ediyor') echo '<span class="badge bg-info text-dark">Devam Ediyor</span>';
                                                else echo '<span class="badge bg-success">Tamamlandı</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($gorev['oncelik'] == 'Yüksek') echo '<span class="text-danger fw-bold">Yüksek</span>';
                                                elseif($gorev['oncelik'] == 'Orta') echo '<span class="text-primary">Orta</span>';
                                                else echo '<span class="text-muted">Düşük</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <div class="avatar-group">
                                                    <div class="avatar-circle bg-primary" title="Yönetici: <?php echo htmlspecialchars($gorev['sahibi_adi']); ?>">
                                                        <?php echo mb_substr(htmlspecialchars($gorev['sahibi_adi']), 0, 1, 'UTF-8'); ?>
                                                    </div>
                                                    <?php 
                                                    $ortak_sorgu = $db->prepare("SELECT k.kullanici_adi FROM gorev_ortaklari go JOIN kullanicilar k ON go.kullanici_id = k.id WHERE go.gorev_id = ? AND go.durum = 'Onaylandı'");
                                                    $ortak_sorgu->execute([$gorev['id']]);
                                                    $ortaklar = $ortak_sorgu->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach($ortaklar as $ortak) {
                                                        echo '<div class="avatar-circle bg-success" title="Ortak: '.htmlspecialchars($ortak['kullanici_adi']).'">';
                                                        echo mb_substr(htmlspecialchars($ortak['kullanici_adi']), 0, 1, 'UTF-8');
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                    <?php if($gorev['kullanici_id'] == $kullanici_id): ?>
                                                        <div class="avatar-circle avatar-add" data-bs-toggle="modal" data-bs-target="#inviteModal<?php echo $gorev['id']; ?>" title="Yeni Ortak Davet Et">+</div>
                                                        <div class="modal fade" id="inviteModal<?php echo $gorev['id']; ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-light py-2">
                                                                        <h6 class="modal-title m-0">Ortak Davet Et</h6>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <form action="" method="POST" class="m-0">
                                                                            <input type="hidden" name="gorev_id" value="<?php echo $gorev['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label small">Kullanıcı E-posta</label>
                                                                                <input type="email" name="email" class="form-control form-control-sm" placeholder="ornek@mail.com" required>
                                                                            </div>
                                                                            <button type="submit" name="davet_et" class="btn btn-primary btn-sm w-100">Davetiye Gönder</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="duzenle.php?id=<?php echo $gorev['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                                <?php if($gorev['kullanici_id'] == $kullanici_id): ?>
                                                    <a href="sil.php?id=<?php echo $gorev['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istiyor musunuz?')">Sil</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title m-0">Profil Ayarları</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label small">Kullanıcı Adı</label>
                        <input type="text" name="yeni_kullanici_adi" class="form-control <?php echo !empty($profil_hata) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($_SESSION['kullanici_adi']); ?>" required>
                        <?php if(!empty($profil_hata)): ?>
                            <div class="invalid-feedback d-block fw-bold"><?php echo $profil_hata; ?></div>
                        <?php endif; ?>
                        <?php if(!empty($profil_basari)): ?>
                            <div class="valid-feedback d-block text-success fw-bold"><?php echo $profil_basari; ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="profil_guncelle" class="btn btn-success w-100 btn-sm">Kullanıcı Adını Güncelle</button>
                </form>
                <hr>
                <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#passwordModal" data-bs-dismiss="modal">
                    Şifreyi Değiştir
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title m-0">Şifre Değiştir</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if(!empty($sifre_hata)): ?>
                    <div class="alert alert-danger p-2 small mb-3"><?php echo $sifre_hata; ?></div>
                <?php endif; ?>
                <?php if(!empty($sifre_basari)): ?>
                    <div class="alert alert-success p-2 small mb-3"><?php echo $sifre_basari; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small">Mevcut Şifreniz</label>
                        <div class="input-group password-group">
                            <input type="password" name="eski_sifre" id="oldPasswordInput" class="form-control" required>
                            <button class="btn" type="button" onclick="togglePasswordVisibility('oldPasswordInput', this)">Göster</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Yeni Şifreniz</label>
                        <div class="input-group password-group">
                            <input type="password" name="yeni_sifre" id="newPasswordInput" class="form-control" required>
                            <button class="btn" type="button" onclick="togglePasswordVisibility('newPasswordInput', this)">Göster</button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#profileModal" data-bs-dismiss="modal">← Geri</button>
                        <button type="submit" name="sifre_degistir" class="btn btn-primary btn-sm">Şifreyi Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text"; btn.textContent = "Gizle";
        } else {
            input.type = "password"; btn.textContent = "Göster";
        }
    }
    
     // modal bir kapanıp açıldığında en son gelen mesajı göstermemesi için kapa aç yapıyoruz
    document.getElementById('profileModal').addEventListener('hidden.bs.modal', function () {
        // feedbakleri siliyor
        this.querySelectorAll('.valid-feedback, .invalid-feedback').forEach(el => el.remove());
        // input çevresi kırmızı çerçeveyi kaldırıoruz
        this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });

    // şifre modalı kapanınca içini temizliyoruz
    document.getElementById('passwordModal').addEventListener('hidden.bs.modal', function () {
        // feedback temizliği
        this.querySelectorAll('.alert').forEach(el => el.remove());
        // inputların içine yazılanları siliyoruz ki tekrar açıldığına şifre ortada olmasın 
        this.querySelector('form').reset();
    });

    const darkModeToggle = document.getElementById('darkModeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        if(darkModeToggle) darkModeToggle.checked = true;
    }
    if(darkModeToggle) {
        darkModeToggle.addEventListener('change', () => {
            if (darkModeToggle.checked) {
                document.body.classList.add('dark-mode'); document.documentElement.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode'); document.documentElement.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
    }

    <?php if(!empty($profil_hata) || !empty($profil_basari)): ?>
    document.addEventListener("DOMContentLoaded", function() { var myModal = new bootstrap.Modal(document.getElementById('profileModal')); myModal.show(); });
    <?php endif; ?>

    <?php if(!empty($sifre_hata) || !empty($sifre_basari)): ?>
    document.addEventListener("DOMContentLoaded", function() { var myModal = new bootstrap.Modal(document.getElementById('passwordModal')); myModal.show(); });
    <?php endif; ?>



</script>
</body>
</html>