<?php
require_once 'db.php';
session_start();

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];

    if(!empty($kullanici_adi) && !empty($sifre)) {
        $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
        $sorgu->execute([$kullanici_adi]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

        if($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            $_SESSION['user_id'] = $kullanici['id'];
            $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
            header("Location: index.php");
            exit;
        } else {
            $mesaj = "<div class='alert alert-danger'>Hatalı kullanıcı adı veya şifre!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* dark mode */
        body.dark-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        .dark-mode .card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .form-control, .dark-mode .form-select { background-color: #2d2d2d !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .text-muted { color: #adb5bd !important; }
        .dark-mode .navbar { background-color: #000 !important; }
        .dark-mode .input-group-text { background-color: #3a3a3a !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #444 !important; }
        .dark-mode .list-group-item { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        .dark-mode .bg-light { background-color: #252525 !important; color: #e0e0e0 !important; border-color: #333 !important; }

        /*  placeholder düzeltmesi */
        .dark-mode .form-control::placeholder { color: #888 !important; opacity: 1; }

        /* mavi efekti kaldırmak için */
        .form-control:focus, .form-select:focus, .btn:focus { box-shadow: none !important; border-color: #ced4da !important; }
        .dark-mode .form-control:focus, .dark-mode .form-select:focus { box-shadow: none !important; border-color: #555 !important; background-color: #333 !important; }

        .dark-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        .dark-mode .dropdown-item { color: #e0e0e0; }
        .dark-mode .dropdown-item:hover { background-color: #333; color: #fff; }
        .dark-mode .dropdown-divider { border-top-color: #444; }
        .dark-mode .form-check-label { color: #e0e0e0 !important; }
        
        /* modal css düzeltmesi */
        .dark-mode .modal-header { border-bottom-color: #444; }
        .dark-mode .modal-header.bg-light { background-color: #252525 !important; color: #fff !important; border-bottom-color: #444; }
        .dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        .dark-mode .table { 
            --bs-table-color: #e0e0e0; --bs-table-bg: #1e1e1e; --bs-table-border-color: #333;
            --bs-table-striped-bg: #252525; --bs-table-striped-color: #e0e0e0;
            --bs-table-hover-bg: #2a2a2a; --bs-table-hover-color: #ffffff;
        }
        .dark-mode select option { background-color: #2d2d2d; color: #fff; }

        /* default scrollbar tasarımını beğenmedim gemini'dan öneri aldım */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        .dark-mode ::-webkit-scrollbar-track { background: #1e1e1e; }
        .dark-mode ::-webkit-scrollbar-thumb { background: #444; }
        .dark-mode ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* katılımcı avatarları */
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
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white"><h4 class="mb-0">Giriş Yap</h4></div>
                <div class="card-body">
                    <?php echo $mesaj; ?>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="kullanici_adi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <div class="input-group password-group">
                                <input type="password" name="sifre" id="loginPassword" class="form-control" required>
                                <button class="btn" type="button" onclick="togglePasswordVisibility('loginPassword', this)">Göster</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Giriş Yap</button>
                    </form>
                    <p class="mt-3 text-center">Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            btn.textContent = "Gizle";
        } else {
            input.type = "password";
            btn.textContent = "Göster";
        }
    }
</script>
</body>
</html>