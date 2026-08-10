<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Usuario.php';

/**
 * @group unit
 */
class UsuarioTest extends TestCase {
    private $usuarioModel;
    private $pdo;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->usuarioModel = new Usuario();
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testCreateAndAuthenticate() {
        $username = 'testuser_' . uniqid();
        $password = 'SecurePass123!';
        $email = $username . '@test.com';

        $data = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'rol' => 'empleado',
            'activo' => 1
        ];

        $result = $this->usuarioModel->create($data);
        $this->assertTrue($result, 'Usuario debe crearse exitosamente');

        $user = $this->usuarioModel->authenticate($username, $password);
        $this->assertNotEmpty($user, 'Autenticacion debe ser exitosa');
        $this->assertEquals($username, $user['username']);
        $this->assertEquals($email, $user['email']);
    }

    public function testAuthenticateFailsWithWrongPassword() {
        $username = 'failuser_' . uniqid();
        $this->usuarioModel->create([
            'username' => $username,
            'password' => 'CorrectPass1',
            'rol' => 'empleado',
            'activo' => 1
        ]);

        $result = $this->usuarioModel->authenticate($username, 'WrongPassword');
        $this->assertFalse($result, 'Autenticacion debe fallar con password incorrecto');
    }

    public function testGetById() {
        $username = 'getbyid_' . uniqid();
        $this->usuarioModel->create([
            'username' => $username,
            'password' => 'TestPass123',
            'email' => $username . '@test.com',
            'rol' => 'admin'
        ]);

        $allUsers = $this->usuarioModel->getAll();
        $created = null;
        foreach ($allUsers as $u) {
            if ($u['username'] === $username) {
                $created = $u;
                break;
            }
        }

        $this->assertNotEmpty($created);
        $user = $this->usuarioModel->getById($created['id']);
        $this->assertNotEmpty($user);
        $this->assertEquals($username, $user['username']);
        $this->assertEquals('admin', $user['rol']);
    }

    public function testRolesHavePermissions() {
        $permisos = Usuario::getPermisosPorRol('admin');
        $this->assertIsArray($permisos);
        $this->assertNotEmpty($permisos, 'Admin debe tener permisos');

        $invitadoPermisos = Usuario::getPermisosPorRol('invitado');
        $this->assertIsArray($invitadoPermisos);
    }
}
