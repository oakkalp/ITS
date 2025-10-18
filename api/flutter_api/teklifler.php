<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
require_once '../../includes/jwt.php';

// JWT Token kontrolü
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    json_error('Authorization header gerekli', 401);
}

try {
    $decoded = JWT::decode($token, JWT_SECRET_KEY);
    if (is_array($decoded)) {
        $decoded = (object) $decoded;
    }
    
    $firma_id = $decoded->firma_id;
    $user_id = $decoded->user_id;
    
} catch (Exception $e) {
    json_error('Geçersiz token', 401);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleGetTeklifler();
        break;
    case 'get':
        handleGetTeklif();
        break;
    case 'detay':
        handleGetTeklifDetay();
        break;
    case 'create':
        handleCreateTeklif();
        break;
    case 'update':
        handleUpdateTeklif();
        break;
    case 'delete':
        handleDeleteTeklif();
        break;
    case 'create_file':
        handleCreateFile();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetTeklifler() {
    global $db, $firma_id;
    
    try {
        $start_date = $_GET['start'] ?? null;
        $end_date = $_GET['end'] ?? null;
        
        $query = "SELECT 
                    t.*, 
                    c.unvan as cari_unvan,
                    c.telefon as cari_telefon,
                    c.email as cari_email
                  FROM teklifler t 
                  LEFT JOIN cariler c ON t.cari_id = c.id 
                  WHERE t.firma_id = ?";
        
        $params = [$firma_id];
        $types = "i";
        
        if ($start_date) {
            $query .= " AND t.teklif_tarihi >= ?";
            $params[] = $start_date;
            $types .= "s";
        }
        
        if ($end_date) {
            $query .= " AND t.teklif_tarihi <= ?";
            $params[] = $end_date;
            $types .= "s";
        }
        
        $query .= " ORDER BY t.teklif_tarihi DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teklifler = [];
        while ($row = $result->fetch_assoc()) {
            $teklifler[] = $row;
        }
        
        json_success('Teklifler başarıyla getirildi', $teklifler);
        
    } catch (Exception $e) {
        error_log("Teklifler listesi hatası: " . $e->getMessage());
        json_error('Teklifler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetTeklif() {
    global $db, $firma_id;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_error('Teklif ID gerekli', 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                t.*,
                c.unvan as cari_unvan,
                c.vergi_no as cari_vergi_no,
                c.vergi_dairesi as cari_vergi_dairesi,
                c.telefon as cari_telefon,
                c.email as cari_email,
                c.adres as cari_adres,
                f.firma_adi,
                f.vergi_dairesi as firma_vergi_dairesi,
                f.vergi_no as firma_vergi_no,
                f.telefon as firma_telefon,
                f.email as firma_email,
                f.adres as firma_adres
            FROM teklifler t
            LEFT JOIN cariler c ON t.cari_id = c.id
            LEFT JOIN firmalar f ON t.firma_id = f.id
            WHERE t.id = ? AND t.firma_id = ?
        ");
        $stmt->bind_param("ii", $id, $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teklif = $result->fetch_assoc();
        
        if (!$teklif) {
            json_error('Teklif bulunamadı', 404);
        }
        
        json_success('Teklif başarıyla getirildi', $teklif);
        
    } catch (Exception $e) {
        error_log("Teklif get hatası: " . $e->getMessage());
        json_error('Teklif getirilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetTeklifDetay() {
    global $db;
    
    $teklif_id = $_GET['teklif_id'] ?? null;
    
    if (!$teklif_id) {
        json_error('Teklif ID gerekli', 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                td.*,
                u.urun_adi,
                u.birim
            FROM teklif_detaylari td
            LEFT JOIN urunler u ON td.urun_id = u.id
            WHERE td.teklif_id = ?
            ORDER BY td.id
        ");
        $stmt->bind_param("i", $teklif_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $detaylar = [];
        while ($row = $result->fetch_assoc()) {
            $detaylar[] = $row;
        }
        
        json_success('Teklif detayları başarıyla getirildi', $detaylar);
        
    } catch (Exception $e) {
        error_log("Teklif detay hatası: " . $e->getMessage());
        json_error('Teklif detayları getirilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleCreateTeklif() {
    global $db, $firma_id, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Teklif numarası oluştur
        $teklif_no = 'TEK-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Teklif oluştur
        $stmt = $db->prepare("
            INSERT INTO teklifler (
                firma_id, teklif_no, teklif_basligi, teklif_tarihi, 
                gecerlilik_tarihi, cari_id, cari_disi_kisi, cari_disi_telefon,
                cari_disi_email, cari_disi_adres, ara_toplam, kdv_tutari, 
                genel_toplam, aciklama, kullanici_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $teklif_basligi = $input['teklif_basligi'] ?? '';
        $teklif_tarihi = $input['teklif_tarihi'] ?? '';
        $gecerlilik_tarihi = $input['gecerlilik_tarihi'] ?? '';
        $cari_id = $input['cari_id'] ?? null;
        $cari_disi_kisi = $input['cari_disi_kisi'] ?? null;
        $cari_disi_telefon = $input['cari_disi_telefon'] ?? null;
        $cari_disi_email = $input['cari_disi_email'] ?? null;
        $cari_disi_adres = $input['cari_disi_adres'] ?? null;
        $ara_toplam = $input['ara_toplam'] ?? 0;
        $kdv_tutari = $input['kdv_tutari'] ?? 0;
        $genel_toplam = $input['genel_toplam'] ?? 0;
        $aciklama = $input['aciklama'] ?? '';
        
        $stmt->bind_param(
            "issssissssdddsi",
            $firma_id, $teklif_no, $teklif_basligi, $teklif_tarihi,
            $gecerlilik_tarihi, $cari_id, $cari_disi_kisi, $cari_disi_telefon,
            $cari_disi_email, $cari_disi_adres, $ara_toplam, $kdv_tutari,
            $genel_toplam, $aciklama, $user_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Teklif oluşturulamadı: ' . $stmt->error);
        }
        
        $teklif_id = $db->insert_id;
        
        // Teklif detaylarını ekle
        if (isset($input['urunler']) && is_array($input['urunler'])) {
            $detay_stmt = $db->prepare("
                INSERT INTO teklif_detaylari (
                    teklif_id, urun_id, aciklama, miktar, birim_fiyat, 
                    kdv_orani, kdv_tutari, toplam
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($input['urunler'] as $urun) {
                $urun_id = $urun['urun_id'] ?? null;
                $aciklama = $urun['aciklama'] ?? '';
                $miktar = $urun['miktar'] ?? 0;
                $birim_fiyat = $urun['birim_fiyat'] ?? 0;
                $kdv_orani = $urun['kdv_orani'] ?? 0;
                $kdv_tutari = $urun['kdv_tutari'] ?? 0;
                $toplam = $urun['toplam'] ?? 0;
                
                $detay_stmt->bind_param(
                    "iisddddd",
                    $teklif_id, $urun_id, $aciklama, $miktar, $birim_fiyat,
                    $kdv_orani, $kdv_tutari, $toplam
                );
                
                if (!$detay_stmt->execute()) {
                    throw new Exception('Teklif detayı eklenemedi: ' . $detay_stmt->error);
                }
            }
        }
        
        $db->commit();
        json_success('Teklif başarıyla oluşturuldu', ['id' => $teklif_id, 'teklif_no' => $teklif_no]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Teklif oluşturma hatası: " . $e->getMessage());
        json_error('Teklif oluşturulurken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleUpdateTeklif() {
    global $db, $firma_id, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_error('Teklif ID gerekli', 400);
    }
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Teklif güncelle
        $stmt = $db->prepare("
            UPDATE teklifler SET 
                teklif_basligi = ?, teklif_tarihi = ?, gecerlilik_tarihi = ?, 
                cari_id = ?, cari_disi_kisi = ?, cari_disi_telefon = ?,
                cari_disi_email = ?, cari_disi_adres = ?, ara_toplam = ?, 
                kdv_tutari = ?, genel_toplam = ?, aciklama = ?
            WHERE id = ? AND firma_id = ?
        ");
        
        $teklif_basligi = $input['teklif_basligi'] ?? '';
        $teklif_tarihi = $input['teklif_tarihi'] ?? '';
        $gecerlilik_tarihi = $input['gecerlilik_tarihi'] ?? '';
        $cari_id = $input['cari_id'] ?? null;
        $cari_disi_kisi = $input['cari_disi_kisi'] ?? null;
        $cari_disi_telefon = $input['cari_disi_telefon'] ?? null;
        $cari_disi_email = $input['cari_disi_email'] ?? null;
        $cari_disi_adres = $input['cari_disi_adres'] ?? null;
        $ara_toplam = $input['ara_toplam'] ?? 0;
        $kdv_tutari = $input['kdv_tutari'] ?? 0;
        $genel_toplam = $input['genel_toplam'] ?? 0;
        $aciklama = $input['aciklama'] ?? '';
        
        $stmt->bind_param(
            "sssissssdddsii",
            $teklif_basligi, $teklif_tarihi, $gecerlilik_tarihi,
            $cari_id, $cari_disi_kisi, $cari_disi_telefon,
            $cari_disi_email, $cari_disi_adres, $ara_toplam,
            $kdv_tutari, $genel_toplam, $aciklama, $id, $firma_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Teklif güncellenemedi: ' . $stmt->error);
        }
        
        // Eski detayları sil
        $delete_stmt = $db->prepare("DELETE FROM teklif_detaylari WHERE teklif_id = ?");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        
        // Yeni detayları ekle
        if (isset($input['urunler']) && is_array($input['urunler'])) {
            $detay_stmt = $db->prepare("
                INSERT INTO teklif_detaylari (
                    teklif_id, urun_id, aciklama, miktar, birim_fiyat, 
                    kdv_orani, kdv_tutari, toplam
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($input['urunler'] as $urun) {
                $urun_id = $urun['urun_id'] ?? null;
                $aciklama = $urun['aciklama'] ?? '';
                $miktar = $urun['miktar'] ?? 0;
                $birim_fiyat = $urun['birim_fiyat'] ?? 0;
                $kdv_orani = $urun['kdv_orani'] ?? 0;
                $kdv_tutari = $urun['kdv_tutari'] ?? 0;
                $toplam = $urun['toplam'] ?? 0;
                
                $detay_stmt->bind_param(
                    "iisddddd",
                    $id, $urun_id, $aciklama, $miktar, $birim_fiyat,
                    $kdv_orani, $kdv_tutari, $toplam
                );
                
                if (!$detay_stmt->execute()) {
                    throw new Exception('Teklif detayı eklenemedi: ' . $detay_stmt->error);
                }
            }
        }
        
        $db->commit();
        json_success('Teklif başarıyla güncellendi', ['id' => $id]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Teklif güncelleme hatası: " . $e->getMessage());
        json_error('Teklif güncellenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleDeleteTeklif() {
    global $db, $firma_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_error('Teklif ID gerekli', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Detayları sil
        $delete_detail_stmt = $db->prepare("DELETE FROM teklif_detaylari WHERE teklif_id = ?");
        $delete_detail_stmt->bind_param("i", $id);
        $delete_detail_stmt->execute();
        
        // Teklifi sil
        $delete_stmt = $db->prepare("DELETE FROM teklifler WHERE id = ? AND firma_id = ?");
        $delete_stmt->bind_param("ii", $id, $firma_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception('Teklif silinemedi: ' . $delete_stmt->error);
        }
        
        if ($delete_stmt->affected_rows === 0) {
            throw new Exception('Teklif bulunamadı');
        }
        
        $db->commit();
        json_success('Teklif başarıyla silindi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Teklif silme hatası: " . $e->getMessage());
        json_error('Teklif silinirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleCreateFile() {
    global $db, $firma_id;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_error('Teklif ID gerekli', 400);
    }
    
    try {
        // Teklif bilgilerini al
        $stmt = $db->prepare("
            SELECT 
                t.*,
                c.unvan as cari_unvan,
                c.telefon as cari_telefon,
                c.email as cari_email,
                c.adres as cari_adres,
                f.firma_adi,
                f.telefon as firma_telefon,
                f.email as firma_email,
                f.adres as firma_adres
            FROM teklifler t
            LEFT JOIN cariler c ON t.cari_id = c.id
            LEFT JOIN firmalar f ON t.firma_id = f.id
            WHERE t.id = ? AND t.firma_id = ?
        ");
        $stmt->bind_param("ii", $id, $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teklif = $result->fetch_assoc();
        
        if (!$teklif) {
            json_error('Teklif bulunamadı', 404);
        }
        
        // Teklif detaylarını al
        $detay_stmt = $db->prepare("
            SELECT 
                td.*,
                u.urun_adi
            FROM teklif_detaylari td
            LEFT JOIN urunler u ON td.urun_id = u.id
            WHERE td.teklif_id = ?
            ORDER BY td.id
        ");
        $detay_stmt->bind_param("i", $id);
        $detay_stmt->execute();
        $detay_result = $detay_stmt->get_result();
        
        $detaylar = [];
        while ($row = $detay_result->fetch_assoc()) {
            $detaylar[] = $row;
        }
        
        // HTML içeriği oluştur
        $html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif - ' . htmlspecialchars($teklif['teklif_no'] ?? '') . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #0d6efd;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 10px 0 5px 0;
            font-size: 20px;
            color: #333;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .company-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .company-info, .customer-info {
            width: 48%;
        }
        
        .company-info h3, .customer-info h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .company-info p, .customer-info p {
            margin: 5px 0;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .info-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-section p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 13px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .products-table .text-right {
            text-align: right;
        }
        
        .products-table .text-right-bold {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 30px;
        }
        
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 15px;
            border: none;
        }
        
        .totals-table .label {
            text-align: left;
            font-weight: bold;
        }
        
        .totals-table .amount {
            text-align: right;
        }
        
        .totals-table .final-row {
            background-color: #e3f2fd;
            font-size: 16px;
            font-weight: bold;
        }
        
        .description-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .description-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        
        .description-section p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Başlık -->
        <div class="header">
            <h1>TEKLİF</h1>
            <h2>' . htmlspecialchars($teklif['teklif_basligi'] ?? '') . '</h2>
            <p class="subtitle">Geçerlilik Tarihi: ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'] ?? date('Y-m-d'))) . '</p>
        </div>
        
        <!-- Firma ve Müşteri Bilgileri -->
        <div class="company-section">
            <div class="company-info">
                <h3>' . htmlspecialchars($teklif['firma_adi'] ?? '') . '</h3>
                <p><strong>Tel:</strong> ' . htmlspecialchars($teklif['firma_telefon'] ?? '') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($teklif['firma_email'] ?? '') . '</p>
                <p><strong>Adres:</strong> ' . htmlspecialchars($teklif['firma_adres'] ?? '') . '</p>
            </div>
            
            <div class="customer-info">
                <h3>Teklif Verilen</h3>
                <p><strong>' . htmlspecialchars($teklif['cari_unvan'] ?? $teklif['cari_disi_kisi'] ?? '') . '</strong></p>
                <p><strong>Tel:</strong> ' . htmlspecialchars($teklif['cari_telefon'] ?? $teklif['cari_disi_telefon'] ?? '') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($teklif['cari_email'] ?? $teklif['cari_disi_email'] ?? '') . '</p>
                <p><strong>Adres:</strong> ' . htmlspecialchars($teklif['cari_adres'] ?? $teklif['cari_disi_adres'] ?? '') . '</p>
            </div>
        </div>
        
        <!-- Teklif Bilgileri -->
        <div class="info-section">
            <p><strong>Teklif No:</strong> ' . htmlspecialchars($teklif['teklif_no'] ?? '') . '</p>
            <p><strong>Teklif Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'] ?? date('Y-m-d'))) . '</p>
            <p><strong>Geçerlilik Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'] ?? date('Y-m-d'))) . '</p>
        </div>
        
        <!-- Ürünler Tablosu -->
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 5%;">Sıra</th>
                    <th style="width: 35%;">Ürün/Hizmet</th>
                    <th style="width: 10%;">Miktar</th>
                    <th style="width: 12%;">Birim Fiyat</th>
                    <th style="width: 8%;">KDV %</th>
                    <th style="width: 12%;">KDV Tutarı</th>
                    <th style="width: 18%;">Toplam</th>
                </tr>
            </thead>
            <tbody>';
        
        $sira = 1;
        foreach ($detaylar as $detay) {
            $urun_adi = $detay['urun_adi'] ?? $detay['aciklama'] ?? '';
            $html .= '<tr>
                        <td class="text-center">' . $sira . '</td>
                        <td>' . htmlspecialchars($urun_adi) . '</td>
                        <td class="text-center">' . number_format($detay['miktar'] ?? 0, 2, ',', '.') . ' adet</td>
                        <td class="text-right">' . number_format($detay['birim_fiyat'] ?? 0, 2, ',', '.') . ' ₺</td>
                        <td class="text-center">%' . number_format($detay['kdv_orani'] ?? 0, 0) . '</td>
                        <td class="text-right">' . number_format($detay['kdv_tutari'] ?? 0, 2, ',', '.') . ' ₺</td>
                        <td class="text-right-bold">' . number_format($detay['toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
                      </tr>';
            $sira++;
        }
        
        $html .= '</tbody>
        </table>
        
        <!-- Toplam Bilgileri -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Ara Toplam:</td>
                    <td class="amount">' . number_format($teklif['ara_toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
                <tr>
                    <td class="label">KDV Toplam:</td>
                    <td class="amount">' . number_format($teklif['kdv_tutari'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
                <tr class="final-row">
                    <td class="label">GENEL TOPLAM:</td>
                    <td class="amount">' . number_format($teklif['genel_toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
            </table>
        </div>';
        
        // Açıklama bölümü
        if (!empty($teklif['aciklama'])) {
            $html .= '<div class="description-section">
                <h4>Açıklama:</h4>
                <p>' . nl2br(htmlspecialchars($teklif['aciklama'])) . '</p>
            </div>';
        }
        
        $html .= '</div>
</body>
</html>';
        
        // Dosya adı
        $filename = 'teklif_' . $teklif['teklif_no'] . '_' . date('Y-m-d_H-i-s') . '.html';
        
        // Geçici dosya oluştur
        $temp_dir = '../../temp/';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $file_path = $temp_dir . $filename;
        file_put_contents($file_path, $html);
        
        json_success('Dosya başarıyla oluşturuldu', [
            'filename' => $filename,
            'download_url' => 'temp/' . $filename
        ]);
        
    } catch (Exception $e) {
        error_log("Dosya oluşturma hatası: " . $e->getMessage());
        json_error('Dosya oluşturulurken hata oluştu: ' . $e->getMessage(), 500);
    }
}
?>