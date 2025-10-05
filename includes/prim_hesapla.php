<?php
/**
 * Prim Hesaplama Fonksiyonu
 * Belirli bir personel için ay bazlı prim hesaplar
 */

if (!function_exists('hesaplaAylikPrim')) {
    function hesaplaAylikPrim($personel_id, $yil, $ay, $db) {
        // Önce bu personelin bu ay için hedeflerini kontrol et
        $hedefler = $db->query("
            SELECT h.*, f.firma_adi 
            FROM hedefler h
            LEFT JOIN firmalar f ON h.firma_id = f.id
            WHERE h.personel_id = ? 
            AND h.yil = ? 
            AND h.ay = ? 
            AND h.durum = 'aktif'
        ", [$personel_id, $yil, $ay]);
        
        // Eğer bu personelin bu ay için hedefi yoksa prim kazanamaz
        if (empty($hedefler)) {
            return [
                'toplam_satis' => 0,
                'toplam_prim' => 0,
                'prim_detaylari' => [],
                'hedef_durumu' => 'hedef_yok',
                'hedef_aciklama' => 'Bu ay için hedef belirlenmemiş'
            ];
        }
        
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
        $hedef_tamamlanan_firma_sayisi = 0;
        $toplam_hedef = 0;
        $hedef_detaylari = [];
        
        // Hedef değerlerini topla
        foreach ($hedefler as $hedef) {
            $toplam_hedef += $hedef['aylik_hedef'];
            $hedef_detaylari[$hedef['firma_id']] = $hedef['aylik_hedef'];
        }
        
        foreach ($firmalar_query as $firma) {
            $firma_id = $firma['firma_id'];
            $firma_adi = $firma['firma_adi'];
            
            // Bu firma için hedef var mı kontrol et
            $firma_hedef = isset($hedef_detaylari[$firma_id]) ? $hedef_detaylari[$firma_id] : 0;
            
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
            
        // Her satış için bu firmaya ait ürünlerin net tutarını hesapla
        $net_satis = 0;
        $firma_toplam_maliyet = 0;
        
        foreach ($satis_ids as $satis_row) {
            $satis_id = $satis_row['id'];
            
            // Bu satışta sadece bu firmaya ait ürünlerin toplam fiyatını al
            $firma_satis_detay = $db->query("
                SELECT 
                    COALESCE(SUM(sd.toplam_fiyat), 0) as firma_tutar
                FROM satis_detay sd
                WHERE sd.satis_id = ?
                AND sd.firma_id = ?
            ", [$satis_id, $firma_id]);
            
            $firma_tutar = !empty($firma_satis_detay) ? (float)$firma_satis_detay[0]['firma_tutar'] : 0;
            
            if ($firma_tutar > 0) {
                // Bu satışın toplam maliyetini al ve firma oranına göre böl
                $satis_toplam = $db->query("SELECT toplam_tutar FROM satislar WHERE id = ?", [$satis_id]);
                $toplam_tutar = !empty($satis_toplam) ? (float)$satis_toplam[0]['toplam_tutar'] : 0;
                
                $maliyet_detay = $db->query("
                    SELECT COALESCE(SUM(maliyet_tutari), 0) as toplam_maliyet
                    FROM satis_maliyetler
                    WHERE satis_id = ?
                ", [$satis_id]);
                
                $toplam_maliyet = !empty($maliyet_detay) ? (float)$maliyet_detay[0]['toplam_maliyet'] : 0;
                
                // Maliyeti firma oranına göre dağıt
                $firma_oran = $toplam_tutar > 0 ? ($firma_tutar / $toplam_tutar) : 0;
                $firma_maliyet = $toplam_maliyet * $firma_oran;
                
                $net_satis += ($firma_tutar - $firma_maliyet);
                $firma_toplam_maliyet += $firma_maliyet;
            }
        }
            
            if ($net_satis > 0) {
                $toplam_satis += $net_satis;
                
                // HEDEF KONTROLÜ: Bu firma için hedef tamamlanmış mı?
                $hedef_tamamlandi = ($firma_hedef > 0 && $net_satis >= $firma_hedef);
                
                if ($hedef_tamamlandi) {
                    $hedef_tamamlanan_firma_sayisi++;
                }
                
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
                
                // SADECE HEDEF TAMAMLANAN FİRMALAR İÇİN PRİM VER
                $prim_tutari = 0;
                if ($hedef_tamamlandi) {
                    $prim_tutari = ($net_satis * $prim_orani) / 100;
                    $toplam_prim += $prim_tutari;
                }
                
                $prim_detaylari[] = [
                    'firma_adi' => $firma_adi,
                    'satis_tutari' => $net_satis,
                    'prim_orani' => $prim_orani,
                    'prim_tutari' => $prim_tutari,
                    'prim_aciklama' => $prim_aciklama,
                    'maliyet_tutari' => $firma_toplam_maliyet,
                    'hedef_tutari' => $firma_hedef,
                    'hedef_tamamlandi' => $hedef_tamamlandi,
                    'hedef_orani' => $firma_hedef > 0 ? ($net_satis / $firma_hedef) * 100 : 0
                ];
            }
        }
        
        // Genel hedef durumu belirle
        $hedef_durumu = 'hedef_var';
        $hedef_aciklama = '';
        
        if ($toplam_hedef == 0) {
            $hedef_durumu = 'hedef_yok';
            $hedef_aciklama = 'Bu ay için hedef belirlenmemiş';
        } elseif ($toplam_satis >= $toplam_hedef) {
            $hedef_durumu = 'hedef_tamamlandi';
            $hedef_aciklama = 'Tüm hedefler tamamlandı';
        } elseif ($hedef_tamamlanan_firma_sayisi > 0) {
            $hedef_durumu = 'kismen_tamamlandi';
            $hedef_aciklama = $hedef_tamamlanan_firma_sayisi . ' firma hedefi tamamlandı';
        } else {
            $hedef_durumu = 'hedef_tamamlanmadi';
            $hedef_aciklama = 'Hiçbir firma hedefi tamamlanmadı';
        }
        
        return [
            'toplam_satis' => $toplam_satis,
            'toplam_prim' => $toplam_prim,
            'prim_detaylari' => $prim_detaylari,
            'hedef_durumu' => $hedef_durumu,
            'hedef_aciklama' => $hedef_aciklama,
            'toplam_hedef' => $toplam_hedef,
            'hedef_tamamlanan_firma_sayisi' => $hedef_tamamlanan_firma_sayisi,
            'hedef_orani' => $toplam_hedef > 0 ? ($toplam_satis / $toplam_hedef) * 100 : 0
        ];
    }
}
?>
