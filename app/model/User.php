<?php

class User {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function findById($userId) {
        $sql = "SELECT user_id, role, username, email, password, is_active, created_at FROM users WHERE user_id = ? LIMIT 1";
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

        $userIdDb = null;
        $role = null;
        $username = null;
        $emailDb = null;
        $passwordHash = null;
        $isActive = null;
        $createdAt = null;

        $stmt->bind_result($userIdDb, $role, $username, $emailDb, $passwordHash, $isActive, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "user_id" => (int)$userIdDb,
            "role" => $role,
            "username" => $username,
            "email" => $emailDb,
            "password" => $passwordHash,
            "is_active" => (int)$isActive,
            "created_at" => $createdAt
        ];
    }

    public function findByEmail($email) {
        $sql = "SELECT user_id, role, username, email, password, is_active, created_at FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->store_result();
        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $userId = null;
        $role = null;
        $username = null;
        $emailDb = null;
        $passwordHash = null;
        $isActive = null;
        $createdAt = null;

        $stmt->bind_result($userId, $role, $username, $emailDb, $passwordHash, $isActive, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "user_id" => (int)$userId,
            "role" => $role,
            "username" => $username,
            "email" => $emailDb,
            "password" => $passwordHash,
            "is_active" => (int)$isActive,
            "created_at" => $createdAt
        ];
    }

    public function findByUsername($username) {
        $sql = "SELECT user_id, role, username, email, password, is_active, created_at FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $stmt->store_result();
        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $userId = null;
        $role = null;
        $usernameDb = null;
        $emailDb = null;
        $passwordHash = null;
        $isActive = null;
        $createdAt = null;

        $stmt->bind_result($userId, $role, $usernameDb, $emailDb, $passwordHash, $isActive, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "user_id" => (int)$userId,
            "role" => $role,
            "username" => $usernameDb,
            "email" => $emailDb,
            "password" => $passwordHash,
            "is_active" => (int)$isActive,
            "created_at" => $createdAt
        ];
    }

    public function existsUsername($username) {
        $sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        $exists = ($stmt->num_rows > 0);
        $stmt->close();
        return $exists;
    }

    public function existsEmail($email) {
        $sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $exists = ($stmt->num_rows > 0);
        $stmt->close();
        return $exists;
    }

    public function existsUsernameExcept($username, $userId) {
        $sql = "SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $stmt->bind_param("si", $username, $uid);
        $stmt->execute();
        $stmt->store_result();

        $exists = ($stmt->num_rows > 0);
        $stmt->close();
        return $exists;
    }

    public function existsEmailExcept($email, $userId) {
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $stmt->bind_param("si", $email, $uid);
        $stmt->execute();
        $stmt->store_result();

        $exists = ($stmt->num_rows > 0);
        $stmt->close();
        return $exists;
    }

    public function create($role, $username, $email, $plainPassword) {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users(role, username, email, password) VALUES(?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $stmt->bind_param("ssss", $role, $username, $email, $hash);

        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;

        $stmt->close();
        return $newId;
    }

    public function verifyLogin($login, $plainPassword) {
        $user = null;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = $this->findByEmail($login);
        } else {
            $user = $this->findByUsername($login);
        }

        if (!$user) return null;
        if ((int)$user["is_active"] !== 1) return null;

        if (!password_verify($plainPassword, $user["password"])) {
            return null;
        }

        return [
            "user_id" => (int)$user["user_id"],
            "role" => $user["role"],
            "username" => $user["username"],
            "email" => $user["email"],
            "is_active" => (int)$user["is_active"],
            "created_at" => $user["created_at"]
        ];
    }

    public function updateBasicInfo($userId, $username, $email) {
        $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $stmt->bind_param("ssi", $username, $email, $uid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function setActive($userId, $isActive) {
        $sql = "UPDATE users SET is_active = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $active = (int)$isActive;
        $uid = (int)$userId;
        $stmt->bind_param("ii", $active, $uid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deactivate($userId) {
        return $this->setActive($userId, 0);
    }

    public function reactivate($userId) {
        return $this->setActive($userId, 1);
    }

    public function updatePassword($userId, $plainPassword) {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $stmt->bind_param("si", $hash, $uid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteAccount($userId) {
        $sql = "DELETE FROM users WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $uid = (int)$userId;
        $stmt->bind_param("i", $uid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
