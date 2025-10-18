<?php
require_once 'config.php';

echo "Creating cekler table...\n";

// Check if table exists
$result = $db->query('SHOW TABLES LIKE "cekler"');
if ($result->num_rows > 0) {
    echo "cekler table already exists!\n";
    exit(0);
}

// Create cekler table
$sql = "CREATE TABLE `cekler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `cek_tipi` enum('alinan','verilen') NOT NULL DEFAULT 'alinan',
  `cek_no` varchar(50) NOT NULL,
  `cari_id` int(11) DEFAULT NULL,
  `cari_disi_kisi` varchar(255) DEFAULT NULL,
  `cek_kaynagi` enum('takas','ciro','verilen') DEFAULT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `banka_adi` varchar(100) NOT NULL,
  `sube` varchar(100) DEFAULT NULL,
  `vade_tarihi` date NOT NULL,
  `durum` enum('portfoy','beklemede','tahsil','odendi','iade','iptal') NOT NULL DEFAULT 'portfoy',
  `aciklama` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `firma_id` (`firma_id`),
  KEY `cari_id` (`cari_id`),
  KEY `vade_tarihi` (`vade_tarihi`),
  KEY `durum` (`durum`),
  CONSTRAINT `cekler_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cekler_ibfk_2` FOREIGN KEY (`cari_id`) REFERENCES `cariler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($db->query($sql) === TRUE) {
    echo "cekler table created successfully!\n";
    
    // Insert sample data
    $sample_data = [
        [
            'firma_id' => 1,
            'kullanici_id' => 1,
            'cek_tipi' => 'alinan',
            'cek_no' => 'CK001',
            'cari_id' => 1,
            'tutar' => 1000.00,
            'banka_adi' => 'Ziraat Bankası',
            'sube' => 'Merkez Şube',
            'vade_tarihi' => date('Y-m-d', strtotime('+30 days')),
            'durum' => 'portfoy',
            'aciklama' => 'Örnek çek kaydı'
        ],
        [
            'firma_id' => 1,
            'kullanici_id' => 1,
            'cek_tipi' => 'verilen',
            'cek_no' => 'CK002',
            'cari_id' => 2,
            'tutar' => 500.00,
            'banka_adi' => 'İş Bankası',
            'sube' => 'Kadıköy Şube',
            'vade_tarihi' => date('Y-m-d', strtotime('+15 days')),
            'durum' => 'beklemede',
            'aciklama' => 'Örnek verilen çek'
        ]
    ];
    
    $stmt = $db->prepare("INSERT INTO cekler (firma_id, kullanici_id, cek_tipi, cek_no, cari_id, tutar, banka_adi, sube, vade_tarihi, durum, aciklama) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sample_data as $data) {
        $stmt->bind_param("iissssdssss",
            $data['firma_id'],
            $data['kullanici_id'],
            $data['cek_tipi'],
            $data['cek_no'],
            $data['cari_id'],
            $data['tutar'],
            $data['banka_adi'],
            $data['sube'],
            $data['vade_tarihi'],
            $data['durum'],
            $data['aciklama']
        );
        
        if ($stmt->execute()) {
            echo "Sample data inserted: " . $data['cek_no'] . "\n";
        } else {
            echo "Error inserting sample data: " . $stmt->error . "\n";
        }
    }
    
    echo "Sample data insertion completed!\n";
    
} else {
    echo "Error creating table: " . $db->error . "\n";
}

$db->close();
?>
