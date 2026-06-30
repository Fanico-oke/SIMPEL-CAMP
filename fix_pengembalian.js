const fs = require('fs');

let c = fs.readFileSync('d:/pemweb/pages/pelanggan/transaksi.php', 'utf8');

// 1. Fix foto_bukti to bukti_foto in submitPengembalian
c = c.replace("formData.append('foto_bukti', fileInput.files[0]);", "formData.append('bukti_foto', fileInput.files[0]);");

// 2. Change the button call (there might be multiple, so split and join)
const oldButton = `<button class="btn-detail-expand" onclick="openPengembalianModal('<?= $rental['reservasi_id'] ?>', '<?= $rental['id'] ?>', '<?= htmlspecialchars($rental['barang']) ?>')"`;
const newButton = `<button class="btn-detail-expand" onclick="openPengembalianModal('<?= $rental['reservasi_id'] ?>', '<?= $rental['trx_id'] ?>', '<?= $rental['id'] ?>', '<?= htmlspecialchars($rental['barang']) ?>', '<?= $rental['kembali'] ?>')"`;
c = c.split(oldButton).join(newButton);

// 3. Update the modal HTML to include an alert box for the fine
const modalHtmlTarget = `<div class="mb-3">
                    <label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Foto Barang Saat Ini <span class="text-danger">*</span></label>`;
const modalHtmlReplace = `<div id="lateWarningContainer" style="display:none; background:#FEF2F2; border-left:4px solid #EF4444; padding:1rem; border-radius:8px; margin-bottom:1rem;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-circle-fill text-danger me-2" style="font-size:1.2rem;"></i>
                            <div>
                                <h6 class="mb-1 text-danger fw-bold">Anda Terlambat <span id="lateDays">0</span> Hari</h6>
                                <p class="mb-0 text-danger" style="font-size:0.85rem;">Denda keterlambatan: <span id="lateFine" class="fw-bold">Rp 0</span>. Denda ini akan ditambahkan ke tagihan setelah admin memeriksa kondisi barang.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                    <label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Foto Barang Saat Ini <span class="text-danger">*</span></label>`;
c = c.replace(modalHtmlTarget, modalHtmlReplace);

// 4. Update the openPengembalianModal function
const jsTarget = `function openPengembalianModal(reservasiId, kode, items) {
    _returnReservasiId = reservasiId;
    document.getElementById('pRentalId').textContent = kode;
    document.getElementById('pItems').textContent = items;
    document.getElementById('pFotoBarang').value = '';
    
    new bootstrap.Modal(document.getElementById('pengembalianModal')).show();
}`;
const jsReplace = `async function openPengembalianModal(reservasiId, trxId, kode, items, kembali) {
    _returnReservasiId = reservasiId;
    document.getElementById('pRentalId').textContent = kode;
    document.getElementById('pItems').textContent = items;
    document.getElementById('pFotoBarang').value = '';
    
    // Hide warning by default
    document.getElementById('lateWarningContainer').style.display = 'none';
    
    // Show modal first so it doesn't wait
    new bootstrap.Modal(document.getElementById('pengembalianModal')).show();
    
    try {
        // Cek denda via hitung_denda
        const response = await fetch('<?= BASE_URL ?>/api/pengembalian.php?action=hitung_denda', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ transaksi_id: trxId, tanggal_kembali: new Date().toISOString().split('T')[0] })
        });
        const res = await response.json();
        if(res.status === 'success' && res.data.hari_terlambat > 0) {
            document.getElementById('lateDays').textContent = res.data.hari_terlambat;
            document.getElementById('lateFine').textContent = formatRp(res.data.denda_keterlambatan);
            document.getElementById('lateWarningContainer').style.display = 'block';
        }
    } catch(err) {
        console.error("Gagal mengecek denda:", err);
    }
}`;
c = c.replace(jsTarget, jsReplace);

// Let's also remove the duplicate returnModal since it's unneeded, but only if it's there
// Well, we don't strictly HAVE to delete it since it's not used. 
// Just to be safe, I'll let it be.

fs.writeFileSync('d:/pemweb/pages/pelanggan/transaksi.php', c, 'utf8');
console.log('Fixed Pengembalian!');
