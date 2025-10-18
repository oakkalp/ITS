class Stok {
  final int id;
  final String urunKodu;
  final String urunAdi;
  final String? kategori;
  final String birim;
  final double stokMiktari;
  final double kritikStok;
  final double alisFiyati;
  final double satisFiyati;
  final int kdvOrani;
  final int aktif;
  final String olusturmaTarihi;

  Stok({
    required this.id,
    required this.urunKodu,
    required this.urunAdi,
    this.kategori,
    required this.birim,
    required this.stokMiktari,
    required this.kritikStok,
    required this.alisFiyati,
    required this.satisFiyati,
    required this.kdvOrani,
    required this.aktif,
    required this.olusturmaTarihi,
  });

  factory Stok.fromJson(Map<String, dynamic> json) {
    return Stok(
      id: json['id'] ?? 0,
      urunKodu: json['urun_kodu'] ?? '',
      urunAdi: json['urun_adi'] ?? '',
      kategori: json['kategori'],
      birim: json['birim'] ?? 'Adet',
      stokMiktari: (json['stok_miktari'] ?? 0).toDouble(),
      kritikStok: (json['kritik_stok'] ?? 0).toDouble(),
      alisFiyati: (json['alis_fiyati'] ?? 0).toDouble(),
      satisFiyati: (json['satis_fiyati'] ?? 0).toDouble(),
      kdvOrani: json['kdv_orani'] ?? 20,
      aktif: json['aktif'] ?? 1,
      olusturmaTarihi: json['olusturma_tarihi'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'urun_kodu': urunKodu,
      'urun_adi': urunAdi,
      'kategori': kategori,
      'birim': birim,
      'stok_miktari': stokMiktari,
      'kritik_stok': kritikStok,
      'alis_fiyati': alisFiyati,
      'satis_fiyati': satisFiyati,
      'kdv_orani': kdvOrani,
      'aktif': aktif,
      'olusturma_tarihi': olusturmaTarihi,
    };
  }

  Stok copyWith({
    int? id,
    String? urunKodu,
    String? urunAdi,
    String? kategori,
    String? birim,
    double? stokMiktari,
    double? kritikStok,
    double? alisFiyati,
    double? satisFiyati,
    int? kdvOrani,
    int? aktif,
    String? olusturmaTarihi,
  }) {
    return Stok(
      id: id ?? this.id,
      urunKodu: urunKodu ?? this.urunKodu,
      urunAdi: urunAdi ?? this.urunAdi,
      kategori: kategori ?? this.kategori,
      birim: birim ?? this.birim,
      stokMiktari: stokMiktari ?? this.stokMiktari,
      kritikStok: kritikStok ?? this.kritikStok,
      alisFiyati: alisFiyati ?? this.alisFiyati,
      satisFiyati: satisFiyati ?? this.satisFiyati,
      kdvOrani: kdvOrani ?? this.kdvOrani,
      aktif: aktif ?? this.aktif,
      olusturmaTarihi: olusturmaTarihi ?? this.olusturmaTarihi,
    );
  }

  @override
  String toString() {
    return 'Stok(id: $id, urunKodu: $urunKodu, urunAdi: $urunAdi, kategori: $kategori, birim: $birim, stokMiktari: $stokMiktari, kritikStok: $kritikStok, alisFiyati: $alisFiyati, satisFiyati: $satisFiyati, kdvOrani: $kdvOrani, aktif: $aktif, olusturmaTarihi: $olusturmaTarihi)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Stok &&
        other.id == id &&
        other.urunKodu == urunKodu &&
        other.urunAdi == urunAdi &&
        other.kategori == kategori &&
        other.birim == birim &&
        other.stokMiktari == stokMiktari &&
        other.kritikStok == kritikStok &&
        other.alisFiyati == alisFiyati &&
        other.satisFiyati == satisFiyati &&
        other.kdvOrani == kdvOrani &&
        other.aktif == aktif &&
        other.olusturmaTarihi == olusturmaTarihi;
  }

  @override
  int get hashCode {
    return id.hashCode ^
        urunKodu.hashCode ^
        urunAdi.hashCode ^
        kategori.hashCode ^
        birim.hashCode ^
        stokMiktari.hashCode ^
        kritikStok.hashCode ^
        alisFiyati.hashCode ^
        satisFiyati.hashCode ^
        kdvOrani.hashCode ^
        aktif.hashCode ^
        olusturmaTarihi.hashCode;
  }
}
