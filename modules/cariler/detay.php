<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list.php');
    exit;
}

$page_title = 'Cari Detay';
require_once '../../includes/auth.php';

// Flutter'dan gelen token'ı kontrol et
$token = $_GET['token'] ?? null;
if ($token) {
    // Debug log
    error_log("Cari Detay - Token alındı: " . substr($token, 0, 10) . "...");
    
    // Önce kullanıcı tablosunun yapısını kontrol et
    $columns = $db->query("SHOW COLUMNS FROM kullanicilar")->fetch_all(MYSQLI_ASSOC);
    $columnNames = array_column($columns, 'Field');
    error_log("Cari Detay - Kullanıcı tablosu kolonları: " . implode(', ', $columnNames));
    
    // Token kolonu var mı kontrol et
    if (in_array('api_token', $columnNames)) {
        // Token'ı doğrula ve session'a kaydet
        $stmt = $db->prepare("SELECT u.*, f.firma_adi FROM kullanicilar u 
                             LEFT JOIN firmalar f ON u.firma_id = f.id 
                             WHERE u.api_token = ? AND u.aktif = 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            error_log("Cari Detay - Kullanıcı bulundu: " . $user['kullanici_adi']);
            
            // Session bilgilerini kaydet
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
            $_SESSION['ad_soyad'] = $user['ad_soyad'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['firma_id'] = $user['firma_id'];
            $_SESSION['firma_adi'] = $user['firma_adi'] ?? '';
            
            // Yetkileri yükle
            if ($user['rol'] === 'kullanici') {
                $stmt = $db->prepare("SELECT modul_kodu, okuma, yazma, silme FROM kullanici_yetkileri WHERE kullanici_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $yetkiler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $_SESSION['permissions'] = [];
                foreach ($yetkiler as $yetki) {
                    $_SESSION['permissions'][$yetki['modul_kodu']] = [
                        'okuma' => (bool)$yetki['okuma'],
                        'yazma' => (bool)$yetki['yazma'],
                        'silme' => (bool)$yetki['silme']
                    ];
                }
            }
        } else {
            error_log("Cari Detay - Token ile kullanıcı bulunamadı");
        }
    } else {
        error_log("Cari Detay - api_token kolonu bulunamadı");
        // Alternatif: session_token veya başka bir kolon kullan
        if (in_array('session_token', $columnNames)) {
            $stmt = $db->prepare("SELECT u.*, f.firma_adi FROM kullanicilar u 
                                 LEFT JOIN firmalar f ON u.firma_id = f.id 
                                 WHERE u.session_token = ? AND u.aktif = 1");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                error_log("Cari Detay - Kullanıcı bulundu (session_token): " . $user['kullanici_adi']);
                
                // Session bilgilerini kaydet
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['ad_soyad'] = $user['ad_soyad'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['firma_id'] = $user['firma_id'];
                $_SESSION['firma_adi'] = $user['firma_adi'] ?? '';
            }
        }
    }
} else {
    error_log("Cari Detay - Token parametresi bulunamadı");
}

require_login();
require_permission('cariler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-person-lines-fill me-2"></i>Cari Detay</h5>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
</div>

<!-- Cari Bilgileri -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Cari Bilgileri</h6>
    </div>
    <div class="card-body">
        <div class="row" id="cariBilgileri">
            <div class="col-12 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bakiye Özeti -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #d4edda; color: #28a745;">
                <i class="bi bi-arrow-down"></i>
            </div>
            <h3 id="toplamAlacak">0,00 ₺</h3>
            <p>Toplam Alacak</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #f8d7da; color: #dc3545;">
                <i class="bi bi-arrow-up"></i>
            </div>
            <h3 id="toplamBorc">0,00 ₺</h3>
            <p>Toplam Borç</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #d1ecf1; color: #0c5460;">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3 id="bakiye">0,00 ₺</h3>
            <p>Bakiye</p>
        </div>
    </div>
</div>

<!-- Genel Ödeme/Tahsilat -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Genel Ödeme/Tahsilat</h6>
        <div>
            <button class="btn btn-sm btn-success" onclick="genelOdemeTahsilat('tahsilat')">
                <i class="bi bi-cash-coin"></i> Tahsilat Yap
            </button>
            <button class="btn btn-sm btn-danger" onclick="genelOdemeTahsilat('odeme')">
                <i class="bi bi-cash-stack"></i> Ödeme Yap
            </button>
        </div>
    </div>
</div>

<!-- Hareket Geçmişi -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Hareket Geçmişi</h6>
    </div>
    <div class="card-body">
        <table class="table table-hover" id="hareketTable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Açıklama</th>
                    <th>Tip</th>
                    <th>Tutar</th>
                    <th>Bakiye</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Faturalar -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Faturalar</h6>
    </div>
    <div class="card-body">
        <table class="table table-hover" id="faturaTable">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tip</th>
                    <th>Tarih</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
const cariId = <?php echo $id; ?>;

$(document).ready(function() {
    loadCariDetay();
    loadFaturalar();
    loadHareketler();
});

function loadCariDetay() {
    $.get('../../api/cariler/get.php?id=' + cariId, function(response) {
        if (response.success) {
            const cari = response.data;
            const bakiye = parseFloat(cari.bakiye);
            
            let html = `
                <div class="col-md-6">
                    <p><strong>Cari Kodu:</strong> ${cari.cari_kodu || '-'}</p>
                    <p><strong>Ünvan:</strong> ${cari.unvan}</p>
                    <p><strong>Tip:</strong> `;
            
            if (cari.is_musteri == 1) html += '<span class="badge bg-success">Müşteri</span> ';
            if (cari.is_tedarikci == 1) html += '<span class="badge bg-primary">Tedarikçi</span>';
            
            html += `</p>
                    <p><strong>Telefon:</strong> ${cari.telefon || '-'}</p>
                    <p><strong>Email:</strong> ${cari.email || '-'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Vergi Dairesi:</strong> ${cari.vergi_dairesi || '-'}</p>
                    <p><strong>Vergi No:</strong> ${cari.vergi_no || '-'}</p>
                    <p><strong>Yetkili Kişi:</strong> ${cari.yetkili_kisi || '-'}</p>
                    <p><strong>Adres:</strong> ${cari.adres || '-'}</p>
                    <p><strong>Durum:</strong> ${cari.aktif == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'}</p>
                </div>
            `;
            
            $('#cariBilgileri').html(html);
            
            // Bakiye göster
            if (bakiye > 0) {
                $('#toplamAlacak').text(formatMoney(bakiye));
                $('#toplamBorc').text('0,00 ₺');
                $('#bakiye').text(formatMoney(bakiye) + ' Alacak').parent().addClass('text-success');
            } else if (bakiye < 0) {
                $('#toplamAlacak').text('0,00 ₺');
                $('#toplamBorc').text(formatMoney(Math.abs(bakiye)));
                $('#bakiye').text(formatMoney(Math.abs(bakiye)) + ' Borç').parent().addClass('text-danger');
            } else {
                $('#toplamAlacak').text('0,00 ₺');
                $('#toplamBorc').text('0,00 ₺');
                $('#bakiye').text('0,00 ₺');
            }
        } else {
            showError('Cari bulunamadı!');
            setTimeout(() => window.location.href = 'list.php', 1500);
        }
    });
}

function loadFaturalar() {
    $('#faturaTable').DataTable({
        ajax: {
            url: '../../api/cariler/faturalar.php?cari_id=' + cariId,
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'fatura_no' },
            { 
                data: 'fatura_tipi',
                render: function(data) {
                    return data == 'alis' 
                        ? '<span class="badge bg-danger">Alış</span>' 
                        : '<span class="badge bg-success">Satış</span>';
                }
            },
            { 
                data: 'fatura_tarihi',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: 'toplam_tutar',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'odeme_durumu',
                render: function(data) {
                    if (data == 'odendi') return '<span class="badge bg-success">Ödendi</span>';
                    if (data == 'kismi') return '<span class="badge bg-warning">Kısmi</span>';
                    return '<span class="badge bg-danger">Bekliyor</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<a href="../faturalar/view.php?id=' + data.id + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[2, 'desc']]
    });
}

function loadHareketler() {
    // Mevcut DataTable'ı destroy et
    if ($.fn.DataTable.isDataTable('#hareketTable')) {
        $('#hareketTable').DataTable().destroy();
    }
    
    $('#hareketTable').DataTable({
        ajax: {
            url: '../../api/cariler/hareketler.php?cari_id=' + cariId,
            dataSrc: function(json) {
                if (!json.success) {
                    console.error('API Error:', json.message);
                    return [];
                }
                return json.data || [];
            },
            xhrFields: {
                withCredentials: true
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showError('Hareketler yüklenirken hata oluştu: ' + error);
            }
        },
        columns: [
            { 
                data: 'tarih',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { data: 'aciklama' },
            { 
                data: 'tip',
                render: function(data) {
                    if (data == 'fatura_alis') return '<span class="badge bg-danger">Alış Faturası</span>';
                    if (data == 'fatura_satis') return '<span class="badge bg-success">Satış Faturası</span>';
                    if (data == 'odeme') return '<span class="badge bg-info">Ödeme</span>';
                    if (data == 'tahsilat') return '<span class="badge bg-warning">Tahsilat</span>';
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { 
                data: 'tutar',
                render: function(data, type, row) {
                    const tutar = parseFloat(data);
                    const islem = row.tip;
                    let prefix = '';
                    
                    if (islem == 'fatura_alis' || islem == 'odeme') {
                        prefix = '-';
                        return '<span class="text-danger">' + prefix + formatMoney(Math.abs(tutar)) + '</span>';
                    } else {
                        prefix = '+';
                        return '<span class="text-success">' + prefix + formatMoney(Math.abs(tutar)) + '</span>';
                    }
                }
            },
            { 
                data: 'bakiye',
                render: function(data) {
                    const bakiye = parseFloat(data);
                    if (bakiye > 0) {
                        return '<span class="text-success">' + formatMoney(bakiye) + ' Alacak</span>';
                    } else if (bakiye < 0) {
                        return '<span class="text-danger">' + formatMoney(Math.abs(bakiye)) + ' Borç</span>';
                    } else {
                        return '<span class="text-muted">0,00 ₺</span>';
                    }
                }
            }
        ],
        order: [[0, 'desc']]
    });
}

function genelOdemeTahsilat(tip) {
    const title = tip == 'tahsilat' ? 'Tahsilat Yap' : 'Ödeme Yap';
    const icon = tip == 'tahsilat' ? 'cash-coin' : 'cash-stack';
    
    Swal.fire({
        title: title,
        html: `
            <div class="mb-3">
                <label class="form-label">Tutar</label>
                <input type="number" id="tutar" class="form-control" step="0.01" min="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tarih</label>
                <input type="date" id="tarih" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ödeme Yöntemi</label>
                <select id="odeme_yontemi" class="form-control" required>
                    <option value="nakit">Nakit</option>
                    <option value="banka">Banka</option>
                    <option value="cek">Çek</option>
                    <option value="kredi_karti">Kredi Kartı</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Açıklama</label>
                <textarea id="aciklama" class="form-control" rows="3"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: title,
        confirmButtonColor: tip == 'tahsilat' ? '#28a745' : '#dc3545',
        cancelButtonText: 'İptal',
        preConfirm: () => {
            const tutar = document.getElementById('tutar').value;
            const tarih = document.getElementById('tarih').value;
            const odeme_yontemi = document.getElementById('odeme_yontemi').value;
            const aciklama = document.getElementById('aciklama').value;
            
            if (!tutar || !tarih || !odeme_yontemi) {
                Swal.showValidationMessage('Lütfen tüm alanları doldurun');
                return false;
            }
            
            return {
                tutar: parseFloat(tutar),
                tarih: tarih,
                odeme_yontemi: odeme_yontemi,
                aciklama: aciklama
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            data.tip = tip;
            data.cari_id = cariId;
            
            $.ajax({
                url: '../../api/cariler/genel_odeme.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Başarılı!', response.message, 'success');
                        loadCariDetay();
                        loadHareketler();
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr) {
                    showError('İşlem sırasında hata oluştu');
                }
            });
        }
    });
}
</script>
