<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'guncelleme');

$id = $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    header('Location: list.php?error=1');
    exit;
}

// Fatura bilgilerini al
$stmt = $db->prepare("SELECT * FROM faturalar WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$fatura = $result->fetch_assoc()) {
    header('Location: list.php?error=2');
    exit;
}

$page_title = 'Fatura Ödeme';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fatura Ödeme</h3>
                    <div class="card-tools">
                        <a href="list.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Fatura Bilgileri</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Fatura No:</strong></td>
                                    <td><?= htmlspecialchars($fatura['fatura_no']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tarih:</strong></td>
                                    <td><?= date('d.m.Y', strtotime($fatura['fatura_tarihi'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tutar:</strong></td>
                                    <td><?= number_format($fatura['toplam_tutar'], 2) ?> ₺</td>
                                </tr>
                                <tr>
                                    <td><strong>Durum:</strong></td>
                                    <td>
                                        <?php if ($fatura['odendi'] == 1): ?>
                                            <span class="badge badge-success">Ödendi</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Ödenmedi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if ($fatura['odendi'] == 0): ?>
                                <h5>Ödeme İşlemi</h5>
                                <form id="odemeForm">
                                    <div class="form-group">
                                        <label for="odeme_tarihi">Ödeme Tarihi</label>
                                        <input type="date" class="form-control" id="odeme_tarihi" name="odeme_tarihi" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="odeme_tutari">Ödeme Tutarı</label>
                                        <input type="number" class="form-control" id="odeme_tutari" name="odeme_tutari" 
                                               value="<?= $fatura['toplam_tutar'] ?>" step="0.01" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="odeme_yontemi">Ödeme Yöntemi</label>
                                        <select class="form-control" id="odeme_yontemi" name="odeme_yontemi" required>
                                            <option value="nakit">Nakit</option>
                                            <option value="kredi_karti">Kredi Kartı</option>
                                            <option value="banka_havalesi">Banka Havalesi</option>
                                            <option value="cek">Çek</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="aciklama">Açıklama</label>
                                        <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Ödemeyi Kaydet
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Bu fatura zaten ödenmiştir.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#odemeForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            fatura_id: <?= $id ?>,
            odeme_tarihi: $('#odeme_tarihi').val(),
            odeme_tutari: $('#odeme_tutari').val(),
            odeme_yontemi: $('#odeme_yontemi').val(),
            aciklama: $('#aciklama').val()
        };
        
        $.ajax({
            url: '../../api/faturalar/odeme.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: 'Ödeme başarıyla kaydedildi.',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = 'list.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Hata!',
                    text: 'Ödeme kaydedilirken bir hata oluştu.',
                    icon: 'error'
                });
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
