<?php

class Skill {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function listRandomCategories($limit = 10) {
        $sql = "SELECT category_id, category_code, category_name, created_at
                FROM skill_categories
                ORDER BY RAND()
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lim = (int)$limit;
        $stmt->bind_param("i", $lim);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["category_id"] = (int)$row["category_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function listAllCategories($limit = 300) {
        $sql = "SELECT category_id, category_code, category_name, created_at
                FROM skill_categories
                ORDER BY category_name ASC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lim = (int)$limit;
        $stmt->bind_param("i", $lim);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["category_id"] = (int)$row["category_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function listCategoriesFromMentorOfferings($limit = 300) {
        $sql = "
            SELECT DISTINCT sc.category_id, sc.category_code, sc.category_name, sc.created_at
            FROM skill_categories sc
            INNER JOIN mentor_skill_offerings mso ON mso.category_id = sc.category_id
            ORDER BY sc.category_name ASC
            LIMIT ?
        ";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lim = (int)$limit;
        $stmt->bind_param("i", $lim);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["category_id"] = (int)$row["category_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function getCategoriesByIds($ids) {
        if (!is_array($ids)) return [];
        $ids = array_values(array_unique(array_map("intval", $ids)));
        $ids = array_values(array_filter($ids, function($v){ return $v > 0; }));
        if (count($ids) < 1) return [];

        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $types = str_repeat("i", count($ids));

        $sql = "SELECT category_id, category_code, category_name, created_at
                FROM skill_categories
                WHERE category_id IN ($placeholders)
                ORDER BY category_name ASC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["category_id"] = (int)$row["category_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function findCategoryByNameExact($categoryName) {
        $sql = "SELECT category_id, category_code, category_name, created_at
                FROM skill_categories
                WHERE category_name = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $id = null;
        $code = null;
        $name = null;
        $createdAt = null;

        $stmt->bind_result($id, $code, $name, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "category_id" => (int)$id,
            "category_code" => $code,
            "category_name" => $name,
            "created_at" => $createdAt
        ];
    }

    public function findCategoryByCode($categoryCode) {
        $sql = "SELECT category_id, category_code, category_name, created_at
                FROM skill_categories
                WHERE category_code = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("s", $categoryCode);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $id = null;
        $code = null;
        $name = null;
        $createdAt = null;

        $stmt->bind_result($id, $code, $name, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "category_id" => (int)$id,
            "category_code" => $code,
            "category_name" => $name,
            "created_at" => $createdAt
        ];
    }

    private function normalizeCategoryName($name) {
        $name = trim((string)$name);
        $name = preg_replace("/\s+/", " ", $name);
        return $name;
    }

    private function firstLetterForCode($name) {
        $name = trim((string)$name);
        if ($name === "") return "X";

        $letter = strtoupper(substr($name, 0, 1));
        if (!preg_match("/[A-Z]/", $letter)) {
            return "X";
        }
        return $letter;
    }

    private function getNextCategoryNumber() {
        $sql = "SELECT MAX(CAST(SUBSTRING(category_code, 3) AS UNSIGNED)) AS maxnum
                FROM skill_categories
                WHERE category_code REGEXP '^C[A-Za-z][0-9]+$'";

        $result = $this->conn->query($sql);
        if (!$result) return 101;

        $row = $result->fetch_assoc();
        $maxnum = isset($row["maxnum"]) ? (int)$row["maxnum"] : 0;
        $result->free();

        if ($maxnum < 101) return 101;
        return $maxnum + 1;
    }

    private function generateCategoryCode($categoryName) {
        $letter = $this->firstLetterForCode($categoryName);
        $num = $this->getNextCategoryNumber();
        return "C" . $letter . $num;
    }

    public function createCategory($categoryName) {
        $categoryName = $this->normalizeCategoryName($categoryName);
        if (!$categoryName) return 0;

        $categoryCode = $this->generateCategoryCode($categoryName);

        $try = 0;
        while ($this->findCategoryByCode($categoryCode)) {
            $try++;
            if ($try > 20) return 0;
            $categoryCode = $this->generateCategoryCode($categoryName);
        }

        $sql = "INSERT INTO skill_categories(category_code, category_name) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $stmt->bind_param("ss", $categoryCode, $categoryName);
        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();

        return $newId;
    }

    public function getOrCreateCategory($categoryName) {
        $categoryName = $this->normalizeCategoryName($categoryName);
        if (!$categoryName) return 0;

        $found = $this->findCategoryByNameExact($categoryName);
        if ($found) return (int)$found["category_id"];

        return $this->createCategory($categoryName);
    }

    public function createCategoriesFromCommaText($categoriesRaw) {
        $categoriesRaw = (string)$categoriesRaw;
        $arr = array_filter(array_map("trim", explode(",", $categoriesRaw)));

        $created = [];

        foreach ($arr as $name) {
            $name = $this->normalizeCategoryName($name);
            if (!$name) continue;

            $cid = $this->getOrCreateCategory($name);
            if (!$cid) continue;

            $created[] = $cid;
        }

        return array_values(array_unique($created));
    }
}
