<?php
/**
 * Prim Hesaplama Fonksiyonu
 * Belirli bir personel için ay bazlı prim hesaplar
 */

if (!function_exists('hesaplaAylikPrim')) {
    function hesaplaAylikPrim($personel_id, $yil, $ay, $db) {
        // Firma listesini al (bu ay satış yapılan firmalar)
        $firmalar_query = $db->query("
            SELECT DISTINCT f.id as firma_id, f.firma_adi
            FROM satislar s
            INNER JOIN satis_detay sd ON s.id = sd.satis_id
            INNER JOIN urun_hizmet uh ON sd.urun_hizmet_id = uh.id
            INNER JOIN firmalar f ON uh.firma_id = f.id
            WHERE s.personel_id = ? 
            AND YEAR(s.satis_tarihi) = ? 
            AND MONTH(s.satis_tarihi) = ?
            AND s.durum = 'odendi'
            AND s.onay_durumu = 'onaylandi'
        ", [$personel_id, $yil, $ay]);
        
        $toplam_prim = 0;
        $toplam_satis = 0;
        $prim_detaylari = [];
        
        foreach ($firmalar_query as $firma) {
            $firma_id = $firma['firma_id'];
            $firma_adi = $firma['firma_adi'];
            
            // Bu firma için satışları al
            $satis_ids = $db->query("
                SELECT DISTINCT s.id
                FROM satislar s
                INNER JOIN satis_detay sd ON s.id = sd.satis_id
                INNER JOIN urun_hizmet uh ON sd.urun_hizmet_id = uh.id
                WHERE s.personel_id = ? 
                AND YEAR(s.satis_tarihi) = ? 
                AND MONTH(s.satis_tarihi) = ?
                AND uh.firma_id = ?
                AND s.durum = 'odendi'
                AND s.onay_durumu = 'onaylandi'
            ", [$personel_id, $yil, $ay, $firma_id]);
            
            // Her satış için net tutarı hesapla
            $net_satis = 0;
            $firma_toplam_maliyet = 0;
            
            foreach ($satis_ids as $satis_row) {
                $satis_id = $satis_row['id'];
                
                // Bu satışın toplam tutarını ve maliyetini al
                $satis_detay = $db->query("
                    SELECT 
                        s.toplam_tutar,
                        COALESCE(SUM(sm.maliyet_tutari), 0) as toplam_maliyet
                    FROM satislar s
                    LEFT JOIN satis_maliyetler sm ON s.id = sm.satis_id
                    WHERE s.id = ?
                    GROUP BY s.id
                ", [$satis_id]);
                
                if (!empty($satis_detay)) {
                    $toplam = (float)$satis_detay[0]['toplam_tutar'];
                    $maliyet = (float)$satis_detay[0]['toplam_maliyet'];
                    $net_satis += ($toplam - $maliyet);
                    $firma_toplam_maliyet += $maliyet;
                }
            }
            
            if ($net_satis > 0) {
                $toplam_satis += $net_satis;
                
                // Önce firma komisyon oranlarını kontrol et
                $firma_komisyon_oranlari = $db->query("
                    SELECT * FROM firma_komisyon 
                    WHERE firma_id = ? AND durum = 'aktif'
                    ORDER BY min_fiyat ASC
                ", [$firma_id]);
                
                $prim_orani = 0;
                $prim_aciklama = '';
                
                if (!empty($firma_komisyon_oranlari)) {
                    // Firma bazlı komisyon oranları var
                    foreach ($firma_komisyon_oranlari as $oran) {
                        if ($net_satis >= $oran['min_fiyat'] && $net_satis <= $oran['max_fiyat']) {
                            $prim_orani = $oran['komisyon_orani'];
                            $prim_aciklama = 'Firma Komisyonu: ₺' . number_format($oran['min_fiyat'], 0, ',', '.') . ' - ₺' . number_format($oran['max_fiyat'], 0, ',', '.');
                            break;
                        }
                    }
                } else {
                    // Firma komisyonu yoksa, personel bazlı prim oranlarını kontrol et
                    $personel_prim_oranlari = $db->query("
                        SELECT * FROM personel_prim_oranlari 
                        WHERE personel_id = ? AND firma_id = ? AND yil = ? AND ay = ? AND durum = 'aktif'
                        ORDER BY min_tutar ASC
                    ", [$personel_id, $firma_id, $yil, $ay]);
                    
                    if (!empty($personel_prim_oranlari)) {
                        // Personel bazlı özel oranlar var
                        foreach ($personel_prim_oranlari as $oran) {
                            if ($net_satis >= $oran['min_tutar'] && $net_satis <= $oran['max_tutar']) {
                                $prim_orani = $oran['prim_orani'];
                                $prim_aciklama = $oran['aciklama'];
                                break;
                            }
                        }
                    } else {
                        // Genel prim oranlarını kullan
                        $genel_prim_oranlari = $db->select('prim_oranlari', ['durum' => 'aktif'], 'min_tutar ASC');
                        foreach ($genel_prim_oranlari as $oran) {
                            if ($net_satis >= $oran['min_tutar'] && $net_satis <= $oran['max_tutar']) {
                                $prim_orani = $oran['prim_orani'];
                                $prim_aciklama = $oran['aciklama'];
                                break;
                            }
                        }
                    }
                }
                
                $prim_tutari = ($net_satis * $prim_orani) / 100;
                $toplam_prim += $prim_tutari;
                
                $prim_detaylari[] = [
                    'firma_adi' => $firma_adi,
                    'satis_tutari' => $net_satis,
                    'prim_orani' => $prim_orani,
                    'prim_tutari' => $prim_tutari,
                    'prim_aciklama' => $prim_aciklama,
                    'maliyet_tutari' => $firma_toplam_maliyet
                ];
            }
        }
        
        return [
            'toplam_satis' => $toplam_satis,
            'toplam_prim' => $toplam_prim,
            'prim_detaylari' => $prim_detaylari
        ];
    }
}
?>
