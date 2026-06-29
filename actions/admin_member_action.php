<?php
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/MemberReward.php';
require_once dirname(__DIR__) . '/classes/MemberBenefit.php';

requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'add_reward') {
        MemberReward::create($_POST);
        setFlashMessage('success', 'Reward berhasil ditambahkan.');
    } elseif ($action === 'edit_reward' && !empty($id)) {
        MemberReward::update($id, $_POST);
        setFlashMessage('success', 'Reward berhasil diperbarui.');
    } elseif ($action === 'add_benefit') {
        MemberBenefit::create($_POST);
        setFlashMessage('success', 'Keuntungan member berhasil ditambahkan.');
    } elseif ($action === 'edit_benefit' && !empty($id)) {
        MemberBenefit::update($id, $_POST);
        setFlashMessage('success', 'Keuntungan member berhasil diperbarui.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';

    if ($action === 'delete_reward' && !empty($id)) {
        MemberReward::delete($id);
        setFlashMessage('success', 'Reward berhasil dihapus.');
    } elseif ($action === 'delete_benefit' && !empty($id)) {
        MemberBenefit::delete($id);
        setFlashMessage('success', 'Keuntungan member berhasil dihapus.');
    }
}

header('Location: ' . BASE_URL . '/pages/admin/kelola_member.php');
exit;
