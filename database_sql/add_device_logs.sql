-- Tabla para logs de dispositivos biométricos
CREATE TABLE IF NOT EXISTS device_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    operation VARCHAR(50) NOT NULL,
    response_data TEXT,
    status ENUM('success', 'error', 'warning') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_logs_device (device_id),
    INDEX idx_device_logs_operation (operation),
    INDEX idx_device_logs_created (created_at)
);