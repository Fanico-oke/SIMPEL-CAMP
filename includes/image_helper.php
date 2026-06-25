<?php
/**
 * Helper untuk gambar barang
 * Include di halaman yang perlu menampilkan gambar barang
 */

// Fallback images per kategori (Unsplash URLs)
$GLOBALS['_categoryFallbackImages'] = [
    'Shelter & Tenda' => 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?w=600&q=80',
    'Perlengkapan Tidur' => 'https://images.unsplash.com/photo-1510672981848-a1c4f1cb5ccf?w=600&q=80',
    'Dapur Lapangan' => 'https://images.unsplash.com/photo-1556909114-44e3e70034e2?w=600&q=80',
    'Penerangan & Elektronik' => 'https://images.unsplash.com/photo-1530541930197-ff16ac917b0e?w=600&q=80',
    'Pakaian & Alas Kaki' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=600&q=80',
    'Keselamatan & Medis' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=600&q=80',
    'Peralatan Pendukung' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?w=600&q=80',
    'Navigasi & Orientasi' => 'https://images.unsplash.com/photo-1452421822248-d4c2b47f0c81?w=600&q=80',
    'Pemurnian Air' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600&q=80',
    'Alat Bantu Jalan' => 'https://images.unsplash.com/photo-1622260614153-03223fb72052?w=600&q=80',
    'Pertukangan & Survival' => 'https://images.unsplash.com/photo-1510672981848-a1c4f1cb5ccf?w=600&q=80',
    'Kenyamanan Camp' => 'https://images.unsplash.com/photo-1487730116645-74489c55551f?w=600&q=80',
    'Komunikasi & Keamanan' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=600&q=80',
];

/**
 * Ambil URL gambar barang
 * Prioritas: file lokal > fallback per kategori > default image
 */
function getBarangImageUrl($barang) {
    // 1. Cek file gambar lokal
    if (!empty($barang['gambar'])) {
        $localPath = dirname(__DIR__) . '/frontend/img/barang/' . $barang['gambar'];
        if (file_exists($localPath)) {
            return ASSETS_URL . '/img/barang/' . $barang['gambar'];
        }
    }
    
    // 2. Fallback berdasarkan kategori
    $kategori = $barang['kategori_nama'] ?? '';
    if (isset($GLOBALS['_categoryFallbackImages'][$kategori])) {
        return $GLOBALS['_categoryFallbackImages'][$kategori];
    }
    
    // 3. Default image
    return 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?w=600&q=80';
}
