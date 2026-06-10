<?php
require_once 'db.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$kullanici_id = $_SESSION['user_id'];
$mesaj = "";

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$gorev_id = $_GET['id'];

$sahip_sorgu = $db->prepare("SELECT g.*, k.kullanici_adi as sahibi_adi, k.email as sahibi_email FROM gorevler g JOIN kullanicilar k ON g.kullanici_id = k.id WHERE g.id = ? AND g.kullanici_id = ?");
$sahip_sorgu->execute([$gorev_id, $kullanici_id]);
$gorev = $sahip_sorgu->fetch(PDO::FETCH_ASSOC);

$is_owner = true;

if(!$gorev) {
    $ortak_kontroll = $db->prepare("SELECT g.*, k.kullanici_adi as sahibi_adi, k.email as sahibi_email FROM gorevler g JOIN kullanicilar k ON g.kullanici_id = k.id JOIN gorev_ortaklari go ON g.id = go.gorev_id WHERE g.id = ? AND go.kullanici_id = ? AND go.durum = 'Onaylandı'");
    $ortak_kontroll->execute([$gorev_id, $kullanici_id]);
    $gorev = $ortak_kontroll->fetch(PDO::FETCH_ASSOC);
    
    if($gorev) {
        $is_owner = false; 
    } else {
        header("Location: index.php");
        exit;
    }
}

if (isset($_GET['islem']) && $_GET['islem'] == 'katilimci_cikar' && isset($_GET['ortak_id'])) {
    $ortak_id = $_GET['ortak_id'];
    if ($is_owner) {
        $cikar_sorgu = $db->prepare("DELETE FROM gorev_ortaklari WHERE gorev_id = ? AND kullanici_id = ?");
        $cikar_sorgu->execute([$gorev_id, $ortak_id]);
        $mesaj = "<div class='alert alert-success'>Katılımcı başarıyla görevden çıkarıldı.</div>";
    } else if (!$is_owner && $ortak_id == $kullanici_id) {
        $cikar_sorgu = $db->prepare("DELETE FROM gorev_ortaklari WHERE gorev_id = ? AND kullanici_id = ?");
        $cikar_sorgu->execute([$gorev_id, $kullanici_id]);
        header("Location: index.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gorev_guncelle'])) {
    $durum = $_POST['durum'];
    if($is_owner) {
        $baslik = trim($_POST['baslik']);
        $aciklama = trim($_POST['aciklama']);
        $oncelik = $_POST['oncelik'];

        if(!empty($baslik) && !empty($aciklama)) {
            $guncelle_sorgu = $db->prepare("UPDATE gorevler SET baslik = ?, aciklama = ?, durum = ?, oncelik = ? WHERE id = ?");
            $guncelle_sorgu->execute([$baslik, $aciklama, $durum, $oncelik, $gorev_id]);
            header("Location: index.php");
            exit;
        } else {
            $mesaj = "<div class='alert alert-warning'>Başlık ve açıklama boş bırakılamaz!</div>";
        }
    } else {
        $guncelle_sorgu = $db->prepare("UPDATE gorevler SET durum = ? WHERE id = ?");
        $guncelle_sorgu->execute([$durum, $gorev_id]);
        header("Location: index.php");
        exit;
    }
}

$katilimci_sorgu = $db->prepare("SELECT k.id, k.kullanici_adi, k.email FROM gorev_ortaklari go JOIN kullanicilar k ON go.kullanici_id = k.id WHERE go.gorev_id = ? AND go.durum = 'Onaylandı'");
$katilimci_sorgu->execute([$gorev_id]);
$katilimcilar = $katilimci_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Görevi Düzenle / Katılımcılar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* dark mode seçeneği için gereken cssler */
        body.dark-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        .dark-mode .card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .form-control, .dark-mode .form-select { background-color: #2d2d2d !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .text-muted { color: #adb5bd !important; }
        .dark-mode .navbar { background-color: #000 !important; }
        .dark-mode .input-group-text { background-color: #3a3a3a !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #444 !important; }
        .dark-mode .list-group-item { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .bg-light { background-color: #252525 !important; color: #e0e0e0 !important; border-color: #333 !important; }

        /* darkmode çalıştığında gözükmeyen yazılar vardı onu çözmek için  */
        .dark-mode .form-control::placeholder { color: #888 !important; opacity: 1; }

        /* bootstrapden kaynaklı input arealarda mavi rahatsız edici bi efekt oluşuyordu onu kaldırmak için */
        .form-control:focus, .form-select:focus, .btn:focus { box-shadow: none !important; border-color: #ced4da !important; }
        .dark-mode .form-control:focus, .dark-mode .form-select:focus { box-shadow: none !important; border-color: #555 !important; background-color: #333 !important; }

        /* dropdown ve yazı düzeltmesi */
        .dark-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #333; color: #fff; }
        .dark-mode .dropdown-divider { border-top-color: #444; }
        .dark-mode .form-check-label { color: #e0e0e0 !important; }
        
        /* modal header kısmı dark modda beyaz kalıyordu onu düzelmek için */
        .dark-mode .modal-header { border-bottom-color: #444; }
        .dark-mode .modal-header.bg-light { background-color: #252525 !important; color: #fff !important; border-bottom-color: #444; }
        .dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        .dark-mode .table { 
            --bs-table-color: #e0e0e0; --bs-table-bg: #1e1e1e; --bs-table-border-color: #333;
            --bs-table-striped-bg: #252525; --bs-table-striped-color: #e0e0e0;
            --bs-table-hover-bg: #2a2a2a; --bs-table-hover-color: #ffffff;
        }
        .dark-mode select option { background-color: #2d2d2d; color: #fff; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        .dark-mode ::-webkit-scrollbar-track { background: #1e1e1e; }
        .dark-mode ::-webkit-scrollbar-thumb { background: #444; }
        .dark-mode ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* katılımcılar için görselsiz avatar tasarım cssleri */
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
        <a class="navbar-brand" href="index.php">Ana Ekran</a>
        <a href="index.php" class="btn btn-outline-light btn-sm">← Geri Dön</a>
    </div>
</nav>

<div class="container mt-3 mb-4">
    <div class="row justify-content-center">
        
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header <?php echo $is_owner ? 'bg-warning text-dark' : 'bg-info text-white'; ?>">
                    <h5 class="mb-0"><?php echo $is_owner ? 'Görevi Düzenle (Yönetici)' : 'Görev Durumunu Güncelle (Ortak)'; ?></h5>
                </div>
                <div class="card-body">
                    <?php echo $mesaj; ?>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Görev Başlığı</label>
                            <input type="text" name="baslik" class="form-control" value="<?php echo htmlspecialchars($gorev['baslik']); ?>" <?php echo !$is_owner ? 'disabled' : 'required'; ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3" style="resize: none;" <?php echo !$is_owner ? 'disabled' : 'required'; ?>><?php echo htmlspecialchars($gorev['aciklama']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Öncelik</label>
                            <select name="oncelik" class="form-select" <?php echo !$is_owner ? 'disabled' : ''; ?>>
                                <option value="Düşük" <?php if($gorev['oncelik'] == 'Düşük') echo 'selected'; ?>>Düşük</option>
                                <option value="Orta" <?php if($gorev['oncelik'] == 'Orta') echo 'selected'; ?>>Orta</option>
                                <option value="Yüksek" <?php if($gorev['oncelik'] == 'Yüksek') echo 'selected'; ?>>Yüksek</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-primary fw-bold">Görev Durumu</label>
                            <select name="durum" class="form-select bg-light border-primary">
                                <option value="Beklemede" <?php if($gorev['durum'] == 'Beklemede') echo 'selected'; ?>>Beklemede</option>
                                <option value="Devam Ediyor" <?php if($gorev['durum'] == 'Devam Ediyor') echo 'selected'; ?>>Devam Ediyor</option>
                                <option value="Tamamlandı" <?php if($gorev['durum'] == 'Tamamlandı') echo 'selected'; ?>>Tamamlandı</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="gorev_guncelle" class="btn btn-success">Değişiklikleri Kaydet</button>
                            <a href="index.php" class="btn btn-secondary">İptal Et</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Katılımcı Listesi</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle-sm bg-primary me-2">
                                    <?php echo mb_substr(htmlspecialchars($gorev['sahibi_adi']), 0, 1, 'UTF-8'); ?>
                                </div>
                                <div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($gorev['sahibi_adi']); ?></span>
                                    <br><small class="text-muted">Proje Sahibi - <?php echo htmlspecialchars($gorev['sahibi_email']); ?></small>
                                </div>
                            </div>
                            <span class="badge bg-primary">Sahip</span>
                        </li>

                        <?php if(empty($katilimcilar)): ?>
                            <li class="list-group-item text-muted small">Bu görevde henüz onaylanmış bir ortak bulunmuyor.</li>
                        <?php else: ?>
                            <?php foreach($katilimcilar as $katilimci): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle-sm bg-success me-2">
                                            <?php echo mb_substr(htmlspecialchars($katilimci['kullanici_adi']), 0, 1, 'UTF-8'); ?>
                                        </div>
                                        <div>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($katilimci['kullanici_adi']); ?></span>
                                            <br><small class="text-muted">Katılımcı - <?php echo htmlspecialchars($katilimci['email']); ?></small>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($is_owner): ?>
                                            <a href="duzenle.php?id=<?php echo $gorev_id; ?>&islem=katilimci_cikar&ortak_id=<?php echo $katilimci['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu kullanıcıyı görevden çıkarmak istediğinize emin misiniz?')">Çıkar</a>
                                        <?php elseif (!$is_owner && $katilimci['id'] == $kullanici_id): ?>
                                            <a href="duzenle.php?id=<?php echo $gorev_id; ?>&islem=katilimci_cikar&ortak_id=<?php echo $katilimci['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu görevden çekilmek istediğinize emin misiniz?')">Görevden Çekil</a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>