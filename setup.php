<?php
require_once 'config.php';

try {
    // Intentar conectar con diferentes métodos de autenticación
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";

    // Primero intentar sin SSL
    $options_no_ssl = DB_OPTIONS;
    unset($options_no_ssl[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
    unset($options_no_ssl[PDO::MYSQL_ATTR_SSL_CA]);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options_no_ssl);
    } catch (PDOException $e) {
        // Si falla, intentar con configuración básica
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // Crear base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos 'sistema_biometrico' creada exitosamente.\n";

    // Conectar a la base de datos específica
    $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options_no_ssl);

    // Crear tablas
    $sql = file_get_contents('database.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Tablas creadas exitosamente.\n";
    echo "Configuración completada. El sistema está listo para usar.\n";

} catch (PDOException $e) {
    die("Error durante la configuración: " . $e->getMessage() . "\n\nSolución sugerida:\n1. Verifica que MySQL esté ejecutándose\n2. Si usas MySQL 8.0+, ejecuta: ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';\n3. O cambia las credenciales en config.php\n");
}
?>
