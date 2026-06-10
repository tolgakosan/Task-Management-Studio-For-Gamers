CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_adi` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `sifre` VARCHAR(255) NOT NULL,
  `olusturulma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gorevler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `baslik` VARCHAR(100) NOT NULL,
  `aciklama` TEXT NOT NULL,
  `durum` ENUM('Beklemede', 'Devam Ediyor', 'Tamamlandı') DEFAULT 'Beklemede',
  `oncelik` ENUM('Düşük', 'Orta', 'Yüksek') DEFAULT 'Orta',
  `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gorev_ortaklari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gorev_id` INT NOT NULL,
  `kullanici_id` INT NOT NULL,
  `durum` ENUM('Beklemede', 'Onaylandı', 'Reddedildi') DEFAULT 'Beklemede',
  FOREIGN KEY (`gorev_id`) REFERENCES `gorevler`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;