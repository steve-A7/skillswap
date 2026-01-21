<?php

class LearnerProfile {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function getByUserId($userId) {
        $sql = "SELECT learner_id, user_id, sex, age, educational_qualification, preferred_way_to_learn, profile_picture_path, preferred_payment_method, paypal_email, bkash_number, nagad_number, card_last4, bio, created_at, updated_at FROM learner_profiles WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $uid = (int)$userId;
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $learnerId = null;
        $userIdDb = null;
        $sex = null;
        $age = null;
        $edu = null;
        $learnMode = null;
        $pic = null;
        $payMethod = null;
        $paypal = null;
        $bkash = null;
        $nagad = null;
        $cardLast4 = null;
        $bio = null;
        $createdAt = null;
        $updatedAt = null;

        $stmt->bind_result(
            $learnerId,
            $userIdDb,
            $sex,
            $age,
            $edu,
            $learnMode,
            $pic,
            $payMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $bio,
            $createdAt,
            $updatedAt
        );
        $stmt->fetch();
        $stmt->close();

        return [
            "learner_id" => (int)$learnerId,
            "user_id" => (int)$userIdDb,
            "sex" => $sex,
            "age" => $age !== null ? (int)$age : null,
            "educational_qualification" => $edu,
            "preferred_way_to_learn" => $learnMode,
            "profile_picture_path" => $pic,
            "preferred_payment_method" => $payMethod,
            "paypal_email" => $paypal,
            "bkash_number" => $bkash,
            "nagad_number" => $nagad,
            "card_last4" => $cardLast4,
            "bio" => $bio,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt
        ];
    }

    public function getLearnerIdByUserId($userId) {
        $sql = "SELECT learner_id FROM learner_profiles WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $uid = (int)$userId;
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return 0;
        }

        $learnerId = null;
        $stmt->bind_result($learnerId);
        $stmt->fetch();
        $stmt->close();

        return (int)$learnerId;
    }

    public function create($userId, $p) {
        $sql = "INSERT INTO learner_profiles(user_id, sex, age, educational_qualification, preferred_way_to_learn, profile_picture_path, preferred_payment_method, paypal_email, bkash_number, nagad_number, card_last4, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $sex = $p["sex"] ?? "other";
        $age = isset($p["age"]) && $p["age"] !== "" ? (int)$p["age"] : null;
        $edu = $p["educational_qualification"] ?? null;
        $learnMode = $p["preferred_way_to_learn"] ?? "both";
        $pic = $p["profile_picture_path"] ?? null;
        $payMethod = $p["preferred_payment_method"] ?? "paypal";
        $paypal = $p["paypal_email"] ?? null;
        $bkash = $p["bkash_number"] ?? null;
        $nagad = $p["nagad_number"] ?? null;
        $cardLast4 = $p["card_last4"] ?? null;
        $bio = $p["bio"] ?? null;

        $stmt->bind_param(
            "isisssssssss",
            $uid,
            $sex,
            $age,
            $edu,
            $learnMode,
            $pic,
            $payMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $bio
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateByUserId($userId, $p) {
        $sql = "UPDATE learner_profiles SET sex = ?, age = ?, educational_qualification = ?, preferred_way_to_learn = ?, profile_picture_path = ?, preferred_payment_method = ?, paypal_email = ?, bkash_number = ?, nagad_number = ?, card_last4 = ?, bio = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $sex = $p["sex"] ?? "other";
        $age = isset($p["age"]) && $p["age"] !== "" ? (int)$p["age"] : null;
        $edu = $p["educational_qualification"] ?? null;
        $learnMode = $p["preferred_way_to_learn"] ?? "both";
        $pic = $p["profile_picture_path"] ?? null;
        $payMethod = $p["preferred_payment_method"] ?? "paypal";
        $paypal = $p["paypal_email"] ?? null;
        $bkash = $p["bkash_number"] ?? null;
        $nagad = $p["nagad_number"] ?? null;
        $cardLast4 = $p["card_last4"] ?? null;
        $bio = $p["bio"] ?? null;

        $stmt->bind_param(
            "sisssssssssi",
            $sex,
            $age,
            $edu,
            $learnMode,
            $pic,
            $payMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $bio,
            $uid
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
