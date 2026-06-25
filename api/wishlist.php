<?php
// api/wishlist.php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/Wishlist.php';

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$user = currentUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'count') {
            jsonSuccess(['count' => Wishlist::count($user['id'])], 'OK');
        } elseif ($action === 'check') {
            $barang_id = intval($_GET['barang_id'] ?? 0);
            jsonSuccess(['exists' => Wishlist::exists($user['id'], $barang_id)], 'OK');
        } else {
            $items = Wishlist::getByUser($user['id']);
            jsonSuccess($items, 'Data wishlist');
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        if ($action === 'add') {
            $barang_id = intval($input['barang_id'] ?? 0);
            $jumlah = intval($input['jumlah'] ?? 1);
            if ($barang_id <= 0) jsonError('ID barang tidak valid');
            if ($jumlah <= 0) $jumlah = 1;

            $result = Wishlist::add($user['id'], $barang_id, $jumlah);
            if ($result) {
                $count = Wishlist::count($user['id']);
                jsonSuccess(['count' => $count], 'Barang berhasil ditambahkan ke wishlist');
            } else {
                jsonError('Gagal menambahkan ke wishlist');
            }
        }
        elseif ($action === 'update') {
            $barang_id = intval($input['barang_id'] ?? 0);
            $jumlah = intval($input['jumlah'] ?? 1);
            if ($barang_id <= 0) jsonError('ID barang tidak valid');

            $result = Wishlist::updateQty($user['id'], $barang_id, $jumlah);
            if ($result) {
                jsonSuccess(null, 'Jumlah berhasil diupdate');
            } else {
                jsonError('Gagal mengupdate jumlah');
            }
        }
        elseif ($action === 'update_dates') {
            $barang_id = intval($input['barang_id'] ?? 0);
            $tanggal_mulai = $input['tanggal_mulai'] ?? '';
            $tanggal_selesai = $input['tanggal_selesai'] ?? '';
            if ($barang_id <= 0) jsonError('ID barang tidak valid');
            if (empty($tanggal_mulai) || empty($tanggal_selesai)) jsonError('Tanggal wajib diisi');
            if (strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)) jsonError('Tanggal selesai harus setelah tanggal mulai');

            $result = Wishlist::updateDates($user['id'], $barang_id, $tanggal_mulai, $tanggal_selesai);
            if ($result) {
                jsonSuccess(null, 'Tanggal berhasil diupdate');
            } else {
                jsonError('Gagal mengupdate tanggal');
            }
        }
        elseif ($action === 'remove') {
            $barang_id = intval($input['barang_id'] ?? 0);
            if ($barang_id <= 0) jsonError('ID barang tidak valid');

            $result = Wishlist::remove($user['id'], $barang_id);
            if ($result) {
                $count = Wishlist::count($user['id']);
                jsonSuccess(['count' => $count], 'Barang dihapus dari wishlist');
            } else {
                jsonError('Gagal menghapus dari wishlist');
            }
        }
        elseif ($action === 'clear') {
            Wishlist::clearByUser($user['id']);
            jsonSuccess(null, 'Wishlist dikosongkan');
        }
        else {
            jsonError('Action tidak valid');
        }
        break;

    default:
        jsonError('Method tidak didukung', 405);
}
