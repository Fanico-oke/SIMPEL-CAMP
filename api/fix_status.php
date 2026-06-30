<?php
// api/fix_status.php
require_once dirname(__DIR__) . '/config/database.php';

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. Cari semua transaksi yang statusnya 'aktif'
    $stmt = $db->query("SELECT id, reservasi_id FROM transaksi WHERE status = 'aktif'");
    $transaksiAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countFixed = 0;
    foreach ($transaksiAktif as $trx) {
        if (!empty($trx['reservasi_id'])) {
            // Cek status reservasinya
            $stmtRsv = $db->prepare("SELECT status FROM reservasi WHERE id = ?");
            $stmtRsv->execute([$trx['reservasi_id']]);
            $rsv = $stmtRsv->fetch(PDO::FETCH_ASSOC);

            // Jika status reservasi TIDAK aktif (misalnya nyangkut di 'menunggu_cek')
            if ($rsv && $rsv['status'] !== 'aktif') {
                $db->prepare("UPDATE reservasi SET status = 'aktif' WHERE id = ?")->execute([$trx['reservasi_id']]);
                
                // Jika sudah ada data di tabel pengembalian yang nyangkut, kita hapus agar bisa mengajukan ulang dari bersih
                $db->prepare("DELETE FROM pengembalian WHERE transaksi_id = ?")->execute([$trx['id']]);
                
                $countFixed++;
            }
        }
    }

    $db->commit();
    echo "<h3>Berhasil memulihkan status!</h3>";
    echo "<p>Jumlah data reservasi yang nyangkut dan berhasil dikembalikan ke status 'Aktif': <strong>$countFixed</strong></p>";
    echo "<p>Silakan kembali ke dashboard, lakukan <strong>Hard Reload (CTRL + SHIFT + R)</strong>, dan coba lagi fitur pengembalian/detailnya.</p>";

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<h3>Gagal memulihkan status:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
