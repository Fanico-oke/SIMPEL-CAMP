<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$user = User::getById($_SESSION['user_id']);
$member = MemberLevel::getByUser($_SESSION['user_id']);

// Ambil Riwayat Transaksi
$stmt = $db->prepare("SELECT * FROM transaksi WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$format = $_GET['format'] ?? 'txt';
$filename = 'Data_Pribadi_SimpelCamp_' . date('Ymd_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Profil
    fputcsv($output, ['Profil Pengguna']);
    fputcsv($output, ['Nama Lengkap', $user['nama']]);
    fputcsv($output, ['Email', $user['email']]);
    fputcsv($output, ['No. Telepon', $user['no_telp'] ?: '-']);
    fputcsv($output, ['Alamat', $user['alamat'] ?: '-']);
    fputcsv($output, ['Tanggal Bergabung', date('d F Y, H:i', strtotime($user['created_at']))]);
    fputcsv($output, []);
    
    // Member
    fputcsv($output, ['Status Keanggotaan']);
    fputcsv($output, ['Level Member', ucfirst($member['level'] ?? 'Regular')]);
    fputcsv($output, ['Total Transaksi', ($member['total_transaksi'] ?? 0) . ' kali']);
    fputcsv($output, ['Total Belanja', 'Rp ' . number_format($member['total_sewa'] ?? 0, 0, ',', '.')]);
    fputcsv($output, ['Total Poin', ($member['poin'] ?? 0) . ' poin']);
    fputcsv($output, []);
    
    // Transaksi
    fputcsv($output, ['Riwayat Transaksi']);
    if (empty($riwayat)) {
        fputcsv($output, ['Belum ada riwayat transaksi']);
    } else {
        fputcsv($output, ['Kode', 'Tanggal', 'Total Bayar', 'Status']);
        foreach ($riwayat as $trx) {
            fputcsv($output, [
                $trx['kode_transaksi'],
                date('d M Y', strtotime($trx['created_at'])),
                'Rp ' . number_format($trx['total_bayar'], 0, ',', '.'),
                ucfirst($trx['status'])
            ]);
        }
    }
    fclose($output);
    exit;

} elseif ($format === 'pdf') {
    // Return HTML designed for printing to PDF
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Data Pribadi - <?= htmlspecialchars($user['nama']) ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; color: #333; }
            h1 { text-align: center; border-bottom: 2px solid #2D6A4F; padding-bottom: 10px; color: #2D6A4F; }
            .section { margin-top: 30px; }
            .section h3 { background: #f4f4f4; padding: 10px; border-left: 5px solid #52B788; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            table, th, td { border: 1px solid #ddd; }
            th, td { padding: 12px; text-align: left; }
            th { background: #f9f9f9; }
            .footer { margin-top: 50px; text-align: center; font-size: 0.8rem; color: #777; }
            @media print {
                body { padding: 0; }
                button { display: none; }
            }
        </style>
    </head>
    <body onload="generatePDF()">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            function generatePDF() {
                var element = document.getElementById('pdf-content');
                var opt = {
                    margin:       0.5,
                    filename:     '<?= $filename ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2 },
                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                
                // Add a small delay to ensure rendering, then download and close
                setTimeout(() => {
                    html2pdf().set(opt).from(element).save().then(() => {
                        setTimeout(() => { window.close(); }, 1000);
                    });
                }, 500);
            }
        </script>
        <div id="pdf-content">
            <h1>Laporan Data Pengguna SIMPEL-CAMP</h1>
            <div class="section">
            <h3>1. Profil Pengguna</h3>
            <table>
                <tr><th width="30%">Nama Lengkap</th><td><?= htmlspecialchars($user['nama']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                <tr><th>No. Telepon</th><td><?= htmlspecialchars($user['no_telp'] ?: '-') ?></td></tr>
                <tr><th>Alamat</th><td><?= htmlspecialchars($user['alamat'] ?: '-') ?></td></tr>
                <tr><th>Tanggal Bergabung</th><td><?= date('d F Y, H:i', strtotime($user['created_at'])) ?></td></tr>
            </table>
        </div>
        <div class="section">
            <h3>2. Status Keanggotaan (Member)</h3>
            <table>
                <tr><th width="30%">Level Member</th><td><?= ucfirst($member['level'] ?? 'Regular') ?></td></tr>
                <tr><th>Total Transaksi</th><td><?= $member['total_transaksi'] ?? 0 ?> kali</td></tr>
                <tr><th>Total Belanja</th><td>Rp <?= number_format($member['total_sewa'] ?? 0, 0, ',', '.') ?></td></tr>
                <tr><th>Total Poin</th><td><?= $member['poin'] ?? 0 ?> poin</td></tr>
            </table>
        </div>
        <div class="section">
            <h3>3. Riwayat Transaksi Terakhir</h3>
            <table>
                <thead>
                    <tr><th>Kode</th><th>Tanggal</th><th>Total Bayar</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat)): ?>
                    <tr><td colspan="4" style="text-align:center;">Belum ada riwayat transaksi</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($riwayat, 0, 10) as $trx): ?>
                        <tr>
                            <td><?= htmlspecialchars($trx['kode_transaksi']) ?></td>
                            <td><?= date('d M Y', strtotime($trx['created_at'])) ?></td>
                            <td>Rp <?= number_format($trx['total_bayar'], 0, ',', '.') ?></td>
                            <td><?= ucfirst($trx['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if(count($riwayat) > 10): ?>
                <p style="font-size:0.9rem; color:#666; margin-top:5px;"><i>*Menampilkan 10 transaksi terakhir dari total <?= count($riwayat) ?> transaksi.</i></p>
            <?php endif; ?>
        </div>
        <div class="footer">Dicetak pada <?= date('d F Y, H:i') ?> dari SIMPEL-CAMP</div>
        </div>
    </body>
    </html>
    <?php
    exit;

} else {
    // Should not reach here
    echo "Format tidak didukung.";
    exit;
}
