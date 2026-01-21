<?php

class MentorProfile {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function getByUserId($userId) {
        $sql = "SELECT mentor_id, user_id, sex, age, qualification_experience, language_proficiency, available_for_price_range, payment_method, paypal_email, bkash_number, nagad_number, card_last4, preferred_mentoring, profile_picture_path, bio, created_at, updated_at FROM mentor_profiles WHERE user_id = ? LIMIT 1";
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

        $mentorId = null;
        $userIdDb = null;
        $sex = null;
        $age = null;
        $qualExp = null;
        $language = null;
        $priceRange = null;
        $paymentMethod = null;
        $paypal = null;
        $bkash = null;
        $nagad = null;
        $cardLast4 = null;
        $mentoringMode = null;
        $pic = null;
        $bio = null;
        $createdAt = null;
        $updatedAt = null;

        $stmt->bind_result(
            $mentorId,
            $userIdDb,
            $sex,
            $age,
            $qualExp,
            $language,
            $priceRange,
            $paymentMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $mentoringMode,
            $pic,
            $bio,
            $createdAt,
            $updatedAt
        );
        $stmt->fetch();
        $stmt->close();

        return [
            "mentor_id" => (int)$mentorId,
            "user_id" => (int)$userIdDb,
            "sex" => $sex,
            "age" => $age !== null ? (int)$age : null,
            "qualification_experience" => $qualExp,
            "language_proficiency" => $language,
            "available_for_price_range" => $priceRange,
            "payment_method" => $paymentMethod,
            "paypal_email" => $paypal,
            "bkash_number" => $bkash,
            "nagad_number" => $nagad,
            "card_last4" => $cardLast4,
            "preferred_mentoring" => $mentoringMode,
            "profile_picture_path" => $pic,
            "bio" => $bio,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt
        ];
    }

    public function getMentorIdByUserId($userId) {
        $sql = "SELECT mentor_id FROM mentor_profiles WHERE user_id = ? LIMIT 1";
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

        $mentorId = null;
        $stmt->bind_result($mentorId);
        $stmt->fetch();
        $stmt->close();

        return (int)$mentorId;
    }

    public function create($userId, $p) {
        $sql = "INSERT INTO mentor_profiles(user_id, sex, age, qualification_experience, language_proficiency, available_for_price_range, payment_method, paypal_email, bkash_number, nagad_number, card_last4, preferred_mentoring, profile_picture_path, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $sex = $p["sex"] ?? "other";
        $age = isset($p["age"]) && $p["age"] !== "" ? (int)$p["age"] : null;
        $qualExp = $p["qualification_experience"] ?? null;
        $language = $p["language_proficiency"] ?? null;
        $priceRange = $p["available_for_price_range"] ?? "1000-1999";
        $paymentMethod = $p["payment_method"] ?? "paypal";
        $paypal = $p["paypal_email"] ?? null;
        $bkash = $p["bkash_number"] ?? null;
        $nagad = $p["nagad_number"] ?? null;
        $cardLast4 = $p["card_last4"] ?? null;
        $mentoringMode = $p["preferred_mentoring"] ?? "both";
        $pic = $p["profile_picture_path"] ?? null;
        $bio = $p["bio"] ?? null;

        $stmt->bind_param(
            "isisssssssssss",
            $uid,
            $sex,
            $age,
            $qualExp,
            $language,
            $priceRange,
            $paymentMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $mentoringMode,
            $pic,
            $bio
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateByUserId($userId, $p) {
        $sql = "UPDATE mentor_profiles SET sex = ?, age = ?, qualification_experience = ?, language_proficiency = ?, available_for_price_range = ?, payment_method = ?, paypal_email = ?, bkash_number = ?, nagad_number = ?, card_last4 = ?, preferred_mentoring = ?, profile_picture_path = ?, bio = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $sex = $p["sex"] ?? "other";
        $age = isset($p["age"]) && $p["age"] !== "" ? (int)$p["age"] : null;
        $qualExp = $p["qualification_experience"] ?? null;
        $language = $p["language_proficiency"] ?? null;
        $priceRange = $p["available_for_price_range"] ?? "1000-1999";
        $paymentMethod = $p["payment_method"] ?? "paypal";
        $paypal = $p["paypal_email"] ?? null;
        $bkash = $p["bkash_number"] ?? null;
        $nagad = $p["nagad_number"] ?? null;
        $cardLast4 = $p["card_last4"] ?? null;
        $mentoringMode = $p["preferred_mentoring"] ?? "both";
        $pic = $p["profile_picture_path"] ?? null;
        $bio = $p["bio"] ?? null;

        $stmt->bind_param(
            "sisssssssssssi",
            $sex,
            $age,
            $qualExp,
            $language,
            $priceRange,
            $paymentMethod,
            $paypal,
            $bkash,
            $nagad,
            $cardLast4,
            $mentoringMode,
            $pic,
            $bio,
            $uid
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
