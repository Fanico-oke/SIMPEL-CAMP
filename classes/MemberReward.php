<?php
// classes/MemberReward.php

require_once dirname(__DIR__) . '/config/database.php';

class MemberReward {
    public static function getAll($activeOnly = false) {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM member_rewards";
            if ($activeOnly) $sql .= " WHERE status = 'aktif'";
            $sql .= " ORDER BY poin_dibutuhkan ASC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("MemberReward::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM member_rewards WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("MemberReward::getById error: " . $e->getMessage());
            return null;
        }
    }

    public static function create($data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO member_rewards (nama_reward, poin_dibutuhkan, deskripsi, icon, status) VALUES (:nama_reward, :poin_dibutuhkan, :deskripsi, :icon, :status)");
            $stmt->execute([
                ':nama_reward'     => $data['nama_reward'],
                ':poin_dibutuhkan' => (int)$data['poin_dibutuhkan'],
                ':deskripsi'       => $data['deskripsi'] ?? null,
                ':icon'            => $data['icon'] ?? 'bi-gift',
                ':status'          => $data['status'] ?? 'aktif'
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("MemberReward::create error: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE member_rewards SET nama_reward = :nama_reward, poin_dibutuhkan = :poin_dibutuhkan, deskripsi = :deskripsi, icon = :icon, status = :status WHERE id = :id");
            return $stmt->execute([
                ':id'              => $id,
                ':nama_reward'     => $data['nama_reward'],
                ':poin_dibutuhkan' => (int)$data['poin_dibutuhkan'],
                ':deskripsi'       => $data['deskripsi'] ?? null,
                ':icon'            => $data['icon'] ?? 'bi-gift',
                ':status'          => $data['status'] ?? 'aktif'
            ]);
        } catch (PDOException $e) {
            error_log("MemberReward::update error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM member_rewards WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("MemberReward::delete error: " . $e->getMessage());
            return false;
        }
    }
}
