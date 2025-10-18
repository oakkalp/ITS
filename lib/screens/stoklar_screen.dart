import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/stok.dart';

class StoklarScreen extends StatefulWidget {
  const StoklarScreen({super.key});

  @override
  State<StoklarScreen> createState() => _StoklarScreenState();
}

class _StoklarScreenState extends State<StoklarScreen> {
  List<Stok> _stoklar = [];
  bool _isLoading = false;
  String _searchQuery = '';
  int _currentPage = 1;
  int _totalPages = 1;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadStoklar();
  }

  Future<void> _loadStoklar({bool refresh = false}) async {
    if (_isLoading) return;
    
    setState(() {
      _isLoading = true;
      if (refresh) {
        _currentPage = 1;
        _stoklar.clear();
        _hasMore = true;
      }
    });

    try {
      final result = await ApiService.getStoklar(
        token: await ApiService.getToken(),
        page: _currentPage,
        limit: 25,
        search: _searchQuery.isNotEmpty ? _searchQuery : null,
      );

      if (result['success']) {
        final data = result['data'];
        final List<dynamic> stoklarData = data['stoklar'];
        final pagination = data['pagination'];
        
        final newStoklar = stoklarData.map((json) => Stok.fromJson(json)).toList();
        
        setState(() {
          if (refresh) {
            _stoklar = newStoklar;
          } else {
            _stoklar.addAll(newStoklar);
          }
          _totalPages = pagination['total_pages'];
          _hasMore = _currentPage < _totalPages;
        });
      } else {
        _showErrorSnackBar(result['message'] ?? 'Stoklar yüklenemedi');
      }
    } catch (e) {
      _showErrorSnackBar('Hata: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (!_hasMore || _isLoading) return;
    
    setState(() {
      _currentPage++;
    });
    
    await _loadStoklar();
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }

  void _showStokModal({Stok? stok}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _StokModal(
        stok: stok,
        onSaved: () {
          _loadStoklar(refresh: true);
        },
      ),
    );
  }

  void _showManuelHareketModal(Stok stok) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _ManuelHareketModal(
        stok: stok,
        onSaved: () {
          _loadStoklar(refresh: true);
        },
      ),
    );
  }

  Future<void> _deleteStok(Stok stok) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Ürünü Sil'),
        content: Text('${stok.urunAdi} ürününü silmek istediğinizden emin misiniz?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('İptal'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Sil'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final result = await ApiService.deleteStok(
          token: await ApiService.getToken(),
          stokId: stok.id,
        );

        if (result['success']) {
          _showSuccessSnackBar('Ürün başarıyla silindi');
          _loadStoklar(refresh: true);
        } else {
          _showErrorSnackBar(result['message'] ?? 'Ürün silinemedi');
        }
      } catch (e) {
        _showErrorSnackBar('Hata: $e');
      }
    }
  }

  Color _getStokColor(double stokMiktari) {
    if (stokMiktari <= 0) {
      return Colors.red;
    } else if (stokMiktari <= 10) {
      return Colors.orange;
    } else {
      return Colors.green;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Stok Yönetimi'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            onPressed: () => _showStokModal(),
            icon: const Icon(Icons.add),
            tooltip: 'Yeni Ürün Ekle',
          ),
        ],
      ),
      body: Column(
        children: [
          // Arama çubuğu
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Ürün ara...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        onPressed: () {
                          setState(() {
                            _searchQuery = '';
                          });
                          _loadStoklar(refresh: true);
                        },
                        icon: const Icon(Icons.clear),
                      )
                    : null,
              ),
              onChanged: (value) {
                setState(() {
                  _searchQuery = value;
                });
                // Debounce için timer kullanılabilir
                Future.delayed(const Duration(milliseconds: 500), () {
                  if (_searchQuery == value) {
                    _loadStoklar(refresh: true);
                  }
                });
              },
            ),
          ),
          
          // Stok listesi
          Expanded(
            child: _isLoading && _stoklar.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _stoklar.isEmpty
                    ? const Center(
                        child: Text(
                          'Henüz ürün bulunmuyor',
                          style: TextStyle(fontSize: 16),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () => _loadStoklar(refresh: true),
                        child: ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _stoklar.length + (_hasMore ? 1 : 0),
                          itemBuilder: (context, index) {
                            if (index == _stoklar.length) {
                              // Load more indicator
                              if (_hasMore) {
                                _loadMore();
                                return const Padding(
                                  padding: EdgeInsets.all(16.0),
                                  child: Center(child: CircularProgressIndicator()),
                                );
                              }
                              return const SizedBox.shrink();
                            }

                            final stok = _stoklar[index];
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8),
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: _getStokColor(stok.stokMiktari),
                                  child: Text(
                                    stok.urunKodu.isNotEmpty 
                                        ? stok.urunKodu.substring(0, 1).toUpperCase()
                                        : 'U',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                                title: Text(
                                  stok.urunAdi,
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                subtitle: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('Kod: ${stok.urunKodu}'),
                                    if (stok.kategori != null && stok.kategori!.isNotEmpty)
                                      Text('Kategori: ${stok.kategori}'),
                                    Text('Birim: ${stok.birim}'),
                                    Row(
                                      children: [
                                        Text('Stok: '),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 2,
                                          ),
                                          decoration: BoxDecoration(
                                            color: _getStokColor(stok.stokMiktari),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Text(
                                            stok.stokMiktari.toString(),
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      onPressed: () => _showManuelHareketModal(stok),
                                      icon: const Icon(Icons.inventory_2),
                                      tooltip: 'Manuel Hareket',
                                    ),
                                    IconButton(
                                      onPressed: () => _showStokModal(stok: stok),
                                      icon: const Icon(Icons.edit),
                                      tooltip: 'Düzenle',
                                    ),
                                    IconButton(
                                      onPressed: () => _deleteStok(stok),
                                      icon: const Icon(Icons.delete),
                                      tooltip: 'Sil',
                                      color: Colors.red,
                                    ),
                                  ],
                                ),
                                onTap: () => _showStokModal(stok: stok),
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showStokModal(),
        child: const Icon(Icons.add),
      ),
    );
  }
}

class _StokModal extends StatefulWidget {
  final Stok? stok;
  final VoidCallback onSaved;

  const _StokModal({
    required this.onSaved,
    this.stok,
  });

  @override
  State<_StokModal> createState() => _StokModalState();
}

class _StokModalState extends State<_StokModal> {
  final _formKey = GlobalKey<FormState>();
  final _urunKoduController = TextEditingController();
  final _urunAdiController = TextEditingController();
  final _kategoriController = TextEditingController();
  final _birimController = TextEditingController();
  final _stokMiktariController = TextEditingController();
  final _kritikStokController = TextEditingController();
  final _alisFiyatiController = TextEditingController();
  final _satisFiyatiController = TextEditingController();
  final _kdvOraniController = TextEditingController();
  
  bool _aktif = true;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _birimController.text = 'Adet';
    _kdvOraniController.text = '20';
    
    if (widget.stok != null) {
      _loadStokData();
    } else {
      _generateUrunKodu();
    }
  }

  void _loadStokData() {
    final stok = widget.stok!;
    _urunKoduController.text = stok.urunKodu;
    _urunAdiController.text = stok.urunAdi;
    _kategoriController.text = stok.kategori ?? '';
    _birimController.text = stok.birim;
    _stokMiktariController.text = stok.stokMiktari.toString();
    _kritikStokController.text = stok.kritikStok.toString();
    _alisFiyatiController.text = stok.alisFiyati.toString();
    _satisFiyatiController.text = stok.satisFiyati.toString();
    _kdvOraniController.text = stok.kdvOrani.toString();
    _aktif = stok.aktif == 1;
  }

  Future<void> _generateUrunKodu() async {
    try {
      final result = await ApiService.generateUrunKodu(
        token: await ApiService.getToken(),
      );

      if (result['success']) {
        _urunKoduController.text = result['data']['urun_kodu'];
      }
    } catch (e) {
      // Hata durumunda manuel kod girişi için placeholder
      _urunKoduController.text = '';
    }
  }

  Future<void> _saveStok() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    try {
      final stokData = {
        'urun_kodu': _urunKoduController.text.trim(),
        'urun_adi': _urunAdiController.text.trim(),
        'kategori': _kategoriController.text.trim().isEmpty ? null : _kategoriController.text.trim(),
        'birim': _birimController.text.trim(),
        'stok_miktari': double.tryParse(_stokMiktariController.text) ?? 0,
        'kritik_stok': double.tryParse(_kritikStokController.text) ?? 0,
        'alis_fiyati': double.tryParse(_alisFiyatiController.text) ?? 0,
        'satis_fiyati': double.tryParse(_satisFiyatiController.text) ?? 0,
        'kdv_orani': int.tryParse(_kdvOraniController.text) ?? 20,
        'aktif': _aktif ? 1 : 0,
      };

      Map<String, dynamic> result;
      
      if (widget.stok != null) {
        stokData['id'] = widget.stok!.id;
        result = await ApiService.updateStok(
          token: await ApiService.getToken(),
          stokData: stokData,
        );
      } else {
        result = await ApiService.createStok(
          token: await ApiService.getToken(),
          stokData: stokData,
        );
      }

      if (result['success']) {
        widget.onSaved();
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'İşlem başarılı'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'İşlem başarısız'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Hata: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.9,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  widget.stok != null ? 'Ürün Düzenle' : 'Yeni Ürün Ekle',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          
          // Form
          Expanded(
            child: Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Ürün Kodu
                    TextFormField(
                      controller: _urunKoduController,
                      decoration: const InputDecoration(
                        labelText: 'Ürün Kodu',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Ürün kodu gerekli';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Ürün Adı
                    TextFormField(
                      controller: _urunAdiController,
                      decoration: const InputDecoration(
                        labelText: 'Ürün Adı *',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Ürün adı gerekli';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Kategori
                    TextFormField(
                      controller: _kategoriController,
                      decoration: const InputDecoration(
                        labelText: 'Kategori',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    
                    // Birim
                    TextFormField(
                      controller: _birimController,
                      decoration: const InputDecoration(
                        labelText: 'Birim *',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Birim gerekli';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Stok Miktarı
                    TextFormField(
                      controller: _stokMiktariController,
                      decoration: const InputDecoration(
                        labelText: 'Stok Miktarı',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // Kritik Stok
                    TextFormField(
                      controller: _kritikStokController,
                      decoration: const InputDecoration(
                        labelText: 'Kritik Stok',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // Alış Fiyatı
                    TextFormField(
                      controller: _alisFiyatiController,
                      decoration: const InputDecoration(
                        labelText: 'Alış Fiyatı',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // Satış Fiyatı
                    TextFormField(
                      controller: _satisFiyatiController,
                      decoration: const InputDecoration(
                        labelText: 'Satış Fiyatı',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // KDV Oranı
                    TextFormField(
                      controller: _kdvOraniController,
                      decoration: const InputDecoration(
                        labelText: 'KDV Oranı (%)',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // Aktif Durumu
                    SwitchListTile(
                      title: const Text('Aktif'),
                      subtitle: const Text('Ürün aktif durumda'),
                      value: _aktif,
                      onChanged: (value) {
                        setState(() {
                          _aktif = value;
                        });
                      },
                    ),
                    const SizedBox(height: 32),
                    
                    // Kaydet Butonu
                    SizedBox(
                      width: double.infinity,
                      height: 50,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _saveStok,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          foregroundColor: Colors.white,
                        ),
                        child: _isLoading
                            ? const CircularProgressIndicator(color: Colors.white)
                            : Text(widget.stok != null ? 'Güncelle' : 'Kaydet'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _urunKoduController.dispose();
    _urunAdiController.dispose();
    _kategoriController.dispose();
    _birimController.dispose();
    _stokMiktariController.dispose();
    _kritikStokController.dispose();
    _alisFiyatiController.dispose();
    _satisFiyatiController.dispose();
    _kdvOraniController.dispose();
    super.dispose();
  }
}

class _ManuelHareketModal extends StatefulWidget {
  final Stok stok;
  final VoidCallback onSaved;

  const _ManuelHareketModal({
    required this.stok,
    required this.onSaved,
  });

  @override
  State<_ManuelHareketModal> createState() => _ManuelHareketModalState();
}

class _ManuelHareketModalState extends State<_ManuelHareketModal> {
  final _formKey = GlobalKey<FormState>();
  final _miktarController = TextEditingController();
  final _birimFiyatController = TextEditingController();
  final _belgeNoController = TextEditingController();
  final _aciklamaController = TextEditingController();
  
  String _hareketTipi = 'manuel_giris';
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.7,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Manuel Stok Hareketi',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          
          // Ürün Bilgisi
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.stok.urunAdi,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                Text('Kod: ${widget.stok.urunKodu}'),
                Text('Mevcut Stok: ${widget.stok.stokMiktari} ${widget.stok.birim}'),
              ],
            ),
          ),
          
          // Form
          Expanded(
            child: Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Hareket Tipi
                    const Text(
                      'Hareket Tipi',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: RadioListTile<String>(
                            title: const Text('Giriş'),
                            value: 'manuel_giris',
                            groupValue: _hareketTipi,
                            onChanged: (value) {
                              setState(() {
                                _hareketTipi = value!;
                              });
                            },
                          ),
                        ),
                        Expanded(
                          child: RadioListTile<String>(
                            title: const Text('Çıkış'),
                            value: 'manuel_cikis',
                            groupValue: _hareketTipi,
                            onChanged: (value) {
                              setState(() {
                                _hareketTipi = value!;
                              });
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    
                    // Miktar
                    TextFormField(
                      controller: _miktarController,
                      decoration: const InputDecoration(
                        labelText: 'Miktar *',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Miktar gerekli';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Geçerli bir sayı giriniz';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Birim Fiyat
                    TextFormField(
                      controller: _birimFiyatController,
                      decoration: const InputDecoration(
                        labelText: 'Birim Fiyat',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    
                    // Belge No
                    TextFormField(
                      controller: _belgeNoController,
                      decoration: const InputDecoration(
                        labelText: 'Belge No',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    
                    // Açıklama
                    TextFormField(
                      controller: _aciklamaController,
                      decoration: const InputDecoration(
                        labelText: 'Açıklama',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 3,
                    ),
                    const SizedBox(height: 32),
                    
                    // Kaydet Butonu
                    SizedBox(
                      width: double.infinity,
                      height: 50,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _saveHareket,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          foregroundColor: Colors.white,
                        ),
                        child: _isLoading
                            ? const CircularProgressIndicator(color: Colors.white)
                            : const Text('Kaydet'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _saveHareket() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    try {
      final hareketData = {
        'urun_id': widget.stok.id,
        'hareket_tipi': _hareketTipi,
        'miktar': double.parse(_miktarController.text),
        'birim_fiyat': double.tryParse(_birimFiyatController.text) ?? 0,
        'belge_no': _belgeNoController.text.trim(),
        'aciklama': _aciklamaController.text.trim(),
      };

      final result = await ApiService.manuelStokHareketi(
        token: await ApiService.getToken(),
        hareketData: hareketData,
      );

      if (result['success']) {
        widget.onSaved();
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Hareket kaydedildi'),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'İşlem başarısız'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Hata: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _miktarController.dispose();
    _birimFiyatController.dispose();
    _belgeNoController.dispose();
    _aciklamaController.dispose();
    super.dispose();
  }
}
