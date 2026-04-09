<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateZktecoProcesamientoLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('zkteco_procesamiento_logs');
        if (!$table->exists()) {
            $table->addColumn('dispositivo_id', 'integer', ['null' => true])
                  ->addColumn('fecha_ejecucion', 'datetime')
                  ->addColumn('registros_procesados', 'integer', ['default' => 0])
                  ->addColumn('estado', 'enum', ['values' => ['exito', 'error', 'advertencia', 'procesando'], 'default' => 'procesando'])
                  ->addColumn('detalles', 'text', ['null' => true])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('sede', 'text', ['null' => true])
                  ->addColumn('archivo_nombre', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('archivo_tamano', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('archivo_sha256', 'string', ['limit' => 64, 'null' => true])
                  ->addIndex(['dispositivo_id'])
                  ->addIndex(['fecha_ejecucion'])
                  ->create();
        }
    }
}