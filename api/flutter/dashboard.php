<?php
// Error reporting'i kapat
error_reporting(0);
ini_set('display_errors', 0);

// Config'i yükle
require_once '../../config.php';

// Flutter API base sınıfını yükle
require_once 'flutter_api.php';

/**
 * Flutter Dashboard API
 * Dashboard verileri ve istatistikler
 */

class FlutterDashboardAPI extends FlutterAPI {
    
    /**
     * GET /api/flutter/dashboard/stats
     * Dashboard istatistikleri
     */
    public function getStats() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $firma_id = $payload['firma_id'];
        
        // Temel istatistikler
        $stats = [];
        
        // Cari sayısı
        $cari_query = "SELECT COUNT(*) as count FROM cariler WHERE firma_id = ? AND aktif = 1";
        $stmt = $this->executeQuery($cari_query, [$firma_id]);
        $stats['cari_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Ürün sayısı
        $urun_query = "SELECT COUNT(*) as count FROM urunler WHERE firma_id = ? AND aktif = 1";
        $stmt = $this->executeQuery($urun_query, [$firma_id]);
        $stats['urun_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Fatura sayısı (bu ay)
        $fatura_query = "SELECT COUNT(*) as count FROM faturalar WHERE firma_id = ? AND MONTH(fatura_tarihi) = MONTH(CURRENT_DATE()) AND YEAR(fatura_tarihi) = YEAR(CURRENT_DATE())";
        $stmt = $this->executeQuery($fatura_query, [$firma_id]);
        $stats['aylik_fatura_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Toplam ciro (bu ay)
        $ciro_query = "SELECT COALESCE(SUM(toplam_tutar), 0) as toplam FROM faturalar WHERE firma_id = ? AND MONTH(fatura_tarihi) = MONTH(CURRENT_DATE()) AND YEAR(fatura_tarihi) = YEAR(CURRENT_DATE()) AND fatura_tipi = 'satis'";
        $stmt = $this->executeQuery($ciro_query, [$firma_id]);
        $stats['aylik_ciro'] = $stmt->get_result()->fetch_assoc()['toplam'];
        
        // Bekleyen çekler
        $cek_query = "SELECT COUNT(*) as count FROM cekler WHERE firma_id = ? AND durum = 'bekliyor' AND vade_tarihi >= CURRENT_DATE()";
        $stmt = $this->executeQuery($cek_query, [$firma_id]);
        $stats['bekleyen_cek_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Vadesi yaklaşan çekler (3 gün içinde)
        $yaklasan_cek_query = "SELECT COUNT(*) as count FROM cekler WHERE firma_id = ? AND durum = 'bekliyor' AND vade_tarihi BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)";
        $stmt = $this->executeQuery($yaklasan_cek_query, [$firma_id]);
        $stats['yaklasan_cek_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Kritik stok ürünleri
        $kritik_stok_query = "SELECT COUNT(*) as count FROM urunler WHERE firma_id = ? AND aktif = 1 AND stok_miktari <= kritik_stok";
        $stmt = $this->executeQuery($kritik_stok_query, [$firma_id]);
        $stats['kritik_stok_sayisi'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $this->sendSuccess($stats, 'İstatistikler alındı');
    }
    
    /**
     * GET /api/flutter/dashboard/recent-activities
     * Son aktiviteler
     */
    public function getRecentActivities() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $firma_id = $payload['firma_id'];
        
        $limit = $_GET['limit'] ?? 10;
        $limit = min(50, max(1, (int)$limit));
        
        $activities = [];
        
        // Son faturalar
        $fatura_query = "SELECT 'fatura' as type, id, fatura_no, fatura_tarihi, toplam_tutar, fatura_tipi, 'Fatura oluşturuldu' as description FROM faturalar WHERE firma_id = ? ORDER BY fatura_tarihi DESC LIMIT ?";
        $stmt = $this->executeQuery($fatura_query, [$firma_id, $limit]);
        $faturalar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($faturalar as $fatura) {
            $activities[] = [
                'type' => 'fatura',
                'id' => $fatura['id'],
                'title' => $fatura['fatura_no'],
                'description' => $fatura['description'],
                'amount' => $fatura['toplam_tutar'],
                'date' => $fatura['fatura_tarihi'],
                'icon' => $fatura['fatura_tipi'] === 'satis' ? 'trending-up' : 'trending-down'
            ];
        }
        
        // Son çekler
        $cek_query = "SELECT 'cek' as type, id, cek_no, vade_tarihi, tutar, durum, 'Çek eklendi' as description FROM cekler WHERE firma_id = ? ORDER BY olusturma_tarihi DESC LIMIT ?";
        $stmt = $this->executeQuery($cek_query, [$firma_id, $limit]);
        $cekler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($cekler as $cek) {
            $activities[] = [
                'type' => 'cek',
                'id' => $cek['id'],
                'title' => $cek['cek_no'],
                'description' => $cek['description'],
                'amount' => $cek['tutar'],
                'date' => $cek['vade_tarihi'],
                'icon' => 'credit-card'
            ];
        }
        
        // Son kasa hareketleri
        $kasa_query = "SELECT 'kasa' as type, id, aciklama, tarih, tutar, islem_tipi, 'Kasa hareketi' as description FROM kasa_hareketleri WHERE firma_id = ? ORDER BY tarih DESC LIMIT ?";
        $stmt = $this->executeQuery($kasa_query, [$firma_id, $limit]);
        $kasa_hareketleri = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($kasa_hareketleri as $kasa) {
            $activities[] = [
                'type' => 'kasa',
                'id' => $kasa['id'],
                'title' => $kasa['aciklama'],
                'description' => $kasa['description'],
                'amount' => $kasa['tutar'],
                'date' => $kasa['tarih'],
                'icon' => $kasa['islem_tipi'] === 'gelir' ? 'arrow-up' : 'arrow-down'
            ];
        }
        
        // Tarihe göre sırala
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Limit uygula
        $activities = array_slice($activities, 0, $limit);
        
        $this->sendSuccess($activities, 'Son aktiviteler alındı');
    }
    
    /**
     * GET /api/flutter/dashboard/charts
     * Grafik verileri
     */
    public function getChartData() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $firma_id = $payload['firma_id'];
        
        $chart_type = $_GET['type'] ?? 'monthly';
        
        switch ($chart_type) {
            case 'monthly':
                $this->getMonthlyChartData($firma_id);
                break;
                
            case 'yearly':
                $this->getYearlyChartData($firma_id);
                break;
                
            case 'category':
                $this->getCategoryChartData($firma_id);
                break;
                
            default:
                $this->sendError('Geçersiz grafik türü');
        }
    }
    
    /**
     * Aylık grafik verileri
     */
    private function getMonthlyChartData($firma_id) {
        $query = "SELECT 
                    MONTH(fatura_tarihi) as month,
                    SUM(CASE WHEN fatura_tipi = 'satis' THEN toplam_tutar ELSE 0 END) as gelir,
                    SUM(CASE WHEN fatura_tipi = 'alis' THEN toplam_tutar ELSE 0 END) as gider
                  FROM faturalar 
                  WHERE firma_id = ? AND YEAR(fatura_tarihi) = YEAR(CURRENT_DATE())
                  GROUP BY MONTH(fatura_tarihi)
                  ORDER BY month";
        
        $stmt = $this->executeQuery($query, [$firma_id]);
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        
        $chart_data = [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Gelir',
                    'data' => array_fill(0, 12, 0),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)'
                ],
                [
                    'label' => 'Gider',
                    'data' => array_fill(0, 12, 0),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgba(239, 68, 68, 1)'
                ]
            ]
        ];
        
        foreach ($data as $row) {
            $month_index = $row['month'] - 1;
            $chart_data['datasets'][0]['data'][$month_index] = (float)$row['gelir'];
            $chart_data['datasets'][1]['data'][$month_index] = (float)$row['gider'];
        }
        
        $this->sendSuccess($chart_data, 'Aylık grafik verileri alındı');
    }
    
    /**
     * Yıllık grafik verileri
     */
    private function getYearlyChartData($firma_id) {
        $query = "SELECT 
                    YEAR(fatura_tarihi) as year,
                    SUM(CASE WHEN fatura_tipi = 'satis' THEN toplam_tutar ELSE 0 END) as gelir,
                    SUM(CASE WHEN fatura_tipi = 'alis' THEN toplam_tutar ELSE 0 END) as gider
                  FROM faturalar 
                  WHERE firma_id = ? AND YEAR(fatura_tarihi) >= YEAR(CURRENT_DATE()) - 4
                  GROUP BY YEAR(fatura_tarihi)
                  ORDER BY year";
        
        $stmt = $this->executeQuery($query, [$firma_id]);
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $chart_data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Gelir',
                    'data' => [],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)'
                ],
                [
                    'label' => 'Gider',
                    'data' => [],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgba(239, 68, 68, 1)'
                ]
            ]
        ];
        
        foreach ($data as $row) {
            $chart_data['labels'][] = $row['year'];
            $chart_data['datasets'][0]['data'][] = (float)$row['gelir'];
            $chart_data['datasets'][1]['data'][] = (float)$row['gider'];
        }
        
        $this->sendSuccess($chart_data, 'Yıllık grafik verileri alındı');
    }
    
    /**
     * Kategori grafik verileri
     */
    private function getCategoryChartData($firma_id) {
        $query = "SELECT 
                    u.kategori,
                    SUM(fd.miktar * fd.birim_fiyat) as toplam_tutar
                  FROM fatura_detaylari fd
                  JOIN faturalar f ON fd.fatura_id = f.id
                  JOIN urunler u ON fd.urun_id = u.id
                  WHERE f.firma_id = ? AND f.fatura_tipi = 'satis' AND MONTH(f.fatura_tarihi) = MONTH(CURRENT_DATE())
                  GROUP BY u.kategori
                  ORDER BY toplam_tutar DESC
                  LIMIT 10";
        
        $stmt = $this->executeQuery($query, [$firma_id]);
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $chart_data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Satış Tutarı',
                    'data' => [],
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                        'rgba(255, 99, 255, 0.8)',
                        'rgba(99, 255, 132, 0.8)'
                    ]
                ]
            ]
        ];
        
        foreach ($data as $row) {
            $chart_data['labels'][] = $row['kategori'] ?: 'Kategorisiz';
            $chart_data['datasets'][0]['data'][] = (float)$row['toplam_tutar'];
        }
        
        $this->sendSuccess($chart_data, 'Kategori grafik verileri alındı');
    }
    
    /**
     * GET /api/flutter/dashboard/notifications
     * Bildirimler
     */
    public function getNotifications() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $firma_id = $payload['firma_id'];
        
        $notifications = [];
        
        // Vadesi yaklaşan çekler
        $cek_query = "SELECT cek_no, vade_tarihi, tutar, DATEDIFF(vade_tarihi, CURRENT_DATE()) as kalan_gun
                      FROM cekler 
                      WHERE firma_id = ? AND durum = 'bekliyor' AND vade_tarihi BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)
                      ORDER BY vade_tarihi ASC";
        
        $stmt = $this->executeQuery($cek_query, [$firma_id]);
        $cekler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($cekler as $cek) {
            $notifications[] = [
                'type' => 'cek_vade',
                'title' => 'Çek Vadesi Yaklaşıyor',
                'message' => "Çek No: {$cek['cek_no']} - ₺{$cek['tutar']} - {$cek['kalan_gun']} gün kaldı",
                'priority' => $cek['kalan_gun'] == 0 ? 'high' : 'medium',
                'action' => 'cekler_page',
                'data' => ['cek_id' => $cek['cek_no']]
            ];
        }
        
        // Kritik stok ürünleri
        $stok_query = "SELECT urun_adi, stok_miktari, kritik_stok
                      FROM urunler 
                      WHERE firma_id = ? AND aktif = 1 AND stok_miktari <= kritik_stok
                      ORDER BY stok_miktari ASC
                      LIMIT 5";
        
        $stmt = $this->executeQuery($stok_query, [$firma_id]);
        $stoklar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($stoklar as $stok) {
            $notifications[] = [
                'type' => 'kritik_stok',
                'title' => 'Kritik Stok Uyarısı',
                'message' => "{$stok['urun_adi']} - Stok: {$stok['stok_miktari']} (Kritik: {$stok['kritik_stok']})",
                'priority' => 'high',
                'action' => 'stok_page',
                'data' => ['urun_id' => $stok['urun_adi']]
            ];
        }
        
        // Bekleyen tahsilatlar
        $tahsilat_query = "SELECT f.fatura_no, f.vade_tarihi, f.toplam_tutar, c.unvan,
                          DATEDIFF(f.vade_tarihi, CURRENT_DATE()) as kalan_gun
                          FROM faturalar f
                          JOIN cariler c ON f.cari_id = c.id
                          WHERE f.firma_id = ? AND f.odeme_durumu = 'bekliyor' AND f.fatura_tipi = 'satis'
                          AND f.vade_tarihi BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)
                          ORDER BY f.vade_tarihi ASC";
        
        $stmt = $this->executeQuery($tahsilat_query, [$firma_id]);
        $tahsilatlar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($tahsilatlar as $tahsilat) {
            $notifications[] = [
                'type' => 'tahsilat',
                'title' => 'Tahsilat Bekleniyor',
                'message' => "{$tahsilat['unvan']} - ₺{$tahsilat['toplam_tutar']} - {$tahsilat['kalan_gun']} gün kaldı",
                'priority' => $tahsilat['kalan_gun'] == 0 ? 'high' : 'medium',
                'action' => 'cariler_page',
                'data' => ['cari_id' => $tahsilat['unvan']]
            ];
        }
        
        // Önceliğe göre sırala
        usort($notifications, function($a, $b) {
            $priority_order = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priority_order[$b['priority']] - $priority_order[$a['priority']];
        });
        
        $this->sendSuccess($notifications, 'Bildirimler alındı');
    }
}

// API endpoint routing
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

$dashboard = new FlutterDashboardAPI();

switch ($path) {
    case '/stats':
        $dashboard->getStats();
        break;
        
    case '/recent-activities':
        $dashboard->getRecentActivities();
        break;
        
    case '/charts':
        $dashboard->getChartData();
        break;
        
    case '/notifications':
        $dashboard->getNotifications();
        break;
        
    default:
        $dashboard->sendError('Endpoint bulunamadı', 404);
}
?>
