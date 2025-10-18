<?php
// Geçici dosya indirme sistemi
$filename = $_GET['file'] ?? '';

if (!$filename) {
    die('Dosya adı belirtilmedi');
}

// Güvenlik kontrolü
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    die('Geçersiz dosya adı');
}

$file_path = __DIR__ . '/' . $filename;

if (!file_exists($file_path)) {
    die('Dosya bulunamadı');
}

// Dosya türünü belirle
$extension = pathinfo($filename, PATHINFO_EXTENSION);
if ($extension === 'html') {
    $content_type = 'text/html';
} else {
    $content_type = 'application/octet-stream';
}

// İndirme header'ları
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Dosyayı gönder
readfile($file_path);

// Dosyayı sil (24 saat sonra otomatik silinecek)
// unlink($file_path);
?>
