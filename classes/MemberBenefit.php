<?php
// classes/MemberBenefit.php

require_once dirname(__DIR__) . '/config/database.php';

class MemberBenefit {
    public static function getAll($activeOnly = false) {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM member_benefits";
            if ($activeOnly) $sql .= " WHERE status = 'aktif'";
            $sql .= " ORDER BY id ASC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("MemberBenefit::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM member_benefits WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("MemberBenefit::getById error: " . $e->getMessage());
            return null;
        }
    }

    public static function create($data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO member_benefits (nama_benefit, deskripsi, icon, warna, status) VALUES (:nama_benefit, :deskripsi, :icon, :warna, :status)");
            $stmt->execute([
                ':nama_benefit' => $data['nama_benefit'],
                ':deskripsi'    => $data['deskripsi'] ?? null,
                ':icon'         => $data['icon'] ?? 'bi-star',
                ':warna'        => $data['warna'] ?? 'blue',
                ':status'       => $data['status'] ?? 'aktif'
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("MemberBenefit::create error: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE member_benefits SET nama_benefit = :nama_benefit, deskripsi = :deskripsi, icon = :icon, warna = :warna, status = :status WHERE id = :id");
            return $stmt->execute([
                ':id'           => $id,
                ':nama_benefit' => $data['nama_benefit'],
                ':deskripsi'    => $data['deskripsi'] ?? null,
                ':icon'         => $data['icon'] ?? 'bi-star',
                ':warna'        => $data['warna'] ?? 'blue',
                ':status'       => $data['status'] ?? 'aktif'
            ]);
        } catch (PDOException $e) {
            error_log("MemberBenefit::update error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM member_benefits WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("MemberBenefit::delete error: " . $e->getMessage());
            return false;
        }
    }
}
