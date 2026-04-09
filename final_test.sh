#!/bin/bash

echo "=== FINAL COMPREHENSIVE VALIDATION TEST ==="

# Test 1: Verify server is running
echo "1. Checking server status..."
curl -s "http://localhost:8000" | head -1
echo ""

# Test 2: Verify database connection and data
echo "2. Testing database connection..."
php -r "
require_once 'config.php';
try {
    \$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, DB_OPTIONS);
    echo '✅ Database connection successful\n';
    
    // Check boss user
    \$stmt = \$pdo->prepare('SELECT id, username, empleado_id FROM usuarios WHERE username = \"jefe1_prueba\"');
    \$stmt->execute();
    \$boss = \$stmt->fetch(PDO::FETCH_ASSOC);
    if (\$boss) {
        echo \"✅ Boss user found: {\$boss['username']} (ID: {\$boss['id']}, Empleado_ID: {\$boss['empleado_id']})\n\";
    }
    
    // Check employees under boss
    \$stmt = \$pdo->prepare('SELECT COUNT(*) FROM empleados WHERE jefe_directo_id = ?');
    \$stmt->execute([\$boss['empleado_id']]);
    \$count = \$stmt->fetchColumn();
    echo \"✅ Employees under boss: \$count\n\";
} catch (Exception \$e) {
    echo '❌ Database error: ' . \$e->getMessage() . \"\n\";
}
"

echo ""

# Test 3: Verify controller logic
echo "3. Testing controller logic..."
php test_validation_complete.php | grep "✅\|❌"

echo ""

# Test 4: Check view files exist
echo "4. Checking view files..."
if [ -f "views/validaciones/index.php" ]; then
    echo "✅ Main validation view exists"
else
    echo "❌ Main validation view missing"
fi

if [ -f "views/validaciones/validaciones.js" ]; then
    echo "✅ Validation JavaScript exists"
else
    echo "❌ Validation JavaScript missing"
fi

echo ""

# Test 5: Final instructions
echo "=== READY FOR BROWSER TESTING ==="
echo "📱 Open your browser and go to: http://localhost:8000/login"
echo "👤 Login with: jefe1_prueba / jefe123"
echo "🎯 After login, go to: http://localhost:8000/validaciones"
echo ""
echo "Expected results:"
echo "✅ Should see 3 employee cards (Ana García, Carlos López, María Rodríguez)"
echo "✅ Should see statistics dashboard"
echo "✅ Should be able to filter by area (Ventas)"
echo "✅ Should see validation controls (approve/reject buttons)"
echo ""
echo "If employees don't appear, check browser console for JavaScript errors."
echo "=== TEST COMPLETE ==="