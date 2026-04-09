<!DOCTYPE html>
<html>
<head>
    <?php
    // Cargar config para tener BASE_URL
    require_once __DIR__ . '/config.php';
    ?>
    <title>Debug de Fetch</title>
    <script>
        window.BASE_URL = "<?php echo rtrim(BASE_URL, '/'); ?>";
        async function testFetch(url, method = 'GET', body = null) {
            console.log(`🔍 Testeando ${method} ${url}`);
            
            const options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            if (method === 'POST') {
                options.headers['Content-Type'] = 'application/json';
                options.body = body;
            }
            
            try {
                console.log('📡 Enviando petición...');
                const response = await fetch(url, options);
                console.log('✅ Response recibida:', response.status);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('📊 Data:', data);
                    return data;
                } else {
                    console.error('❌ Response no ok:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            } catch (error) {
                console.error('❌ Error en fetch:', error);
                
                // Intentar con fetch simple
                console.log('🔄 Intentando fetch simple...');
                try {
                    const simpleResponse = await fetch(url);
                    console.log('✅ Simple fetch response:', simpleResponse.status);
                    return await simpleResponse.text();
                } catch (simpleError) {
                    console.error('❌ Simple fetch también falló:', simpleError);
                }
                
                throw error;
            }
        }
        
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('🚀 Iniciando debug de fetch...');
            
            const testUrl = `${window.BASE_URL}/validaciones/buscar-empleados`;
            
            // Test 1: GET con window.BASE_URL
            await testFetch(testUrl, 'GET');
            
            console.log('✅ Debug completado');
        });
    </script>
</head>
<body>
    <h1>Debug de Fetch</h1>
    <p>Abre la consola para ver los resultados</p>
</body>
</html>