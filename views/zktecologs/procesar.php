<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesamiento Web ZKTeco</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { margin-bottom: 20px; }
        .log-container {
            background: #1e1e1e;
            color: #00ff00;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-cog"></i> Procesamiento Web ZKTeco</h4>
            </div>
            <div class="card-body">
                <form id="procesarForm">
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Archivo de Logs</label>
                        <input type="file" class="form-control" id="archivo" name="archivo" accept=".txt,.log,.csv">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i> Procesar
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Log de Procesamiento</h5>
            </div>
            <div class="card-body">
                <div class="log-container" id="logContainer">
                    <div>Listo para procesar...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script>
        $('#procesarForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const $log = $('#logContainer');
            $log.html('<div>Iniciando procesamiento...</div>');

            $.ajax({
                url: BASE_URL + '/zkteco/procesar',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $log.append('<div class="text-success">Procesamiento completado: ' + (response.mensaje || 'OK') + '</div>');
                },
                error: function(xhr) {
                    $log.append('<div class="text-danger">Error: ' + (xhr.responseJSON?.mensaje || xhr.statusText) + '</div>');
                }
            });
        });
    </script>
</body>
</html>
