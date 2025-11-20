<?php
require_once 'Database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function authenticate($username, $password) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO usuarios (username, password, rol, empleado_id)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['username'],
            $hashedPassword,
            $data['rol'] ?? 'usuario',
            $data['empleado_id'] ?? null
        ]);
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $updateFields = [];
        $params = [];

        if (isset($data['username'])) {
            $updateFields[] = "username = ?";
            $params[] = $data['username'];
        }

        if (isset($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['rol'])) {
            $updateFields[] = "rol = ?";
            $params[] = $data['rol'];
        }

        if (isset($data['empleado_id'])) {
            $updateFields[] = "empleado_id = ?";
            $params[] = $data['empleado_id'];
        }

        $params[] = $id;

        $stmt = $this->db->getConnection()->prepare("
            UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?
        ");
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getByUsername($username) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
}
?>
