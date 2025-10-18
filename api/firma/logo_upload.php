<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('firma', 'guncelleme');

try {
    $firma_id = get_firma_id();
    
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        json_error('Dosya yüklenirken hata oluştu', 400);
    }
    
    $file = $_FILES['logo'];
    
    // Dosya boyutu kontrolü (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        json_error('Dosya boyutu 2MB\'dan büyük olamaz', 400);
    }
    
    // Dosya tipi kontrolü
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        json_error('Sadece JPG, PNG ve GIF formatları desteklenir', 400);
    }
    
    // Dosya uzantısını belirle
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'logo_' . $firma_id . '_' . time() . '.' . $extension;
    $uploadPath = '../../uploads/logos/' . $newFileName;
    
    // Eski logoyu sil
    $oldLogo = $db->query("SELECT logo FROM firmalar WHERE id = $firma_id")->fetch_assoc()['logo'];
    if ($oldLogo && file_exists('../../uploads/logos/' . $oldLogo)) {
        unlink('../../uploads/logos/' . $oldLogo);
    }
    
    // Dosyayı yükle
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Veritabanını güncelle
        $stmt = $db->prepare("UPDATE firmalar SET logo = ? WHERE id = ?");
        $stmt->bind_param("si", $newFileName, $firma_id);
        
        if ($stmt->execute()) {
            json_success('Logo başarıyla yüklendi', ['logo' => $newFileName]);
        } else {
            // Dosyayı sil
            unlink($uploadPath);
            json_error('Veritabanı güncellenirken hata oluştu', 500);
        }
    } else {
        json_error('Dosya yüklenirken hata oluştu', 500);
    }
    
} catch (Exception $e) {
    error_log("Logo upload hatası: " . $e->getMessage());
    json_error('Logo yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
