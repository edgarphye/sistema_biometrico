<?php

/**
 * Procesador de datos biométricos
 * Maneja templates, normalización y procesamiento de datos biométricos
 */
class DataProcessor
{
    /**
     * Normaliza datos biométricos para formato estándar
     */
    public static function normalizeBiometricData(array $data, string $type): array
    {
        switch ($type) {
            case 'huella':
                return self::normalizeFingerprintData($data);
            case 'cara':
                return self::normalizeFaceData($data);
            default:
                throw new InvalidArgumentException("Tipo de dato biométrico no soportado: $type");
        }
    }

    /**
     * Normaliza datos de huella dactilar
     */
    private static function normalizeFingerprintData(array $data): array
    {
        return [
            'template' => $data['template'] ?? $data['fingerprint_template'] ?? '',
            'quality' => intval($data['quality'] ?? $data['fingerprint_quality'] ?? 0),
            'finger_index' => intval($data['finger_index'] ?? $data['finger'] ?? 0),
            'minutiae_points' => intval($data['minutiae_points'] ?? $data['minutiae'] ?? 0),
            'ridge_count' => intval($data['ridge_count'] ?? $data['ridges'] ?? 0),
            'capture_time' => $data['capture_time'] ?? date('Y-m-d H:i:s'),
            'algorithm' => $data['algorithm'] ?? 'unknown'
        ];
    }

    /**
     * Normaliza datos faciales
     */
    private static function normalizeFaceData(array $data): array
    {
        return [
            'template' => $data['face_template'] ?? $data['template'] ?? '',
            'confidence' => floatval($data['confidence'] ?? $data['face_confidence'] ?? 0),
            'landmarks' => $data['landmarks'] ?? [],
            'pose' => $data['pose'] ?? ['yaw' => 0, 'pitch' => 0, 'roll' => 0],
            'quality' => intval($data['quality'] ?? $data['face_quality'] ?? 0),
            'capture_time' => $data['capture_time'] ?? date('Y-m-d H:i:s'),
            'algorithm' => $data['algorithm'] ?? 'unknown'
        ];
    }

    /**
     * Valida integridad de datos biométricos
     */
    public static function validateBiometricData(array $data, string $type): bool
    {
        switch ($type) {
            case 'huella':
                return self::validateFingerprintData($data);
            case 'cara':
                return self::validateFaceData($data);
            default:
                return false;
        }
    }

    /**
     * Valida datos de huella
     */
    private static function validateFingerprintData(array $data): bool
    {
        // Verificar campos requeridos
        if (empty($data['template']) || !is_string($data['template'])) {
            return false;
        }

        // Verificar calidad mínima
        if (($data['quality'] ?? 0) < 50) {
            return false;
        }

        // Verificar que el template tenga longitud razonable
        if (strlen($data['template']) < 100) {
            return false;
        }

        return true;
    }

    /**
     * Valida datos faciales
     */
    private static function validateFaceData(array $data): bool
    {
        // Verificar campos requeridos
        if (empty($data['template']) || !is_string($data['template'])) {
            return false;
        }

        // Verificar confianza mínima
        if (($data['confidence'] ?? 0) < 70) {
            return false;
        }

        // Verificar landmarks si existen
        if (isset($data['landmarks']) && !is_array($data['landmarks'])) {
            return false;
        }

        return true;
    }

    /**
     * Calcula calidad general de datos biométricos
     */
    public static function calculateQualityScore(array $data, string $type): int
    {
        switch ($type) {
            case 'huella':
                return self::calculateFingerprintQuality($data);
            case 'cara':
                return self::calculateFaceQuality($data);
            default:
                return 0;
        }
    }

    /**
     * Calcula calidad de huella
     */
    private static function calculateFingerprintQuality(array $data): int
    {
        $score = 0;

        // Calidad base
        $score += min(40, ($data['quality'] ?? 0) * 0.4);

        // Puntos de minutiae
        $minutiae = $data['minutiae_points'] ?? 0;
        $score += min(30, $minutiae * 0.5);

        // Conteo de crestas
        $ridges = $data['ridge_count'] ?? 0;
        $score += min(30, $ridges * 0.2);

        return min(100, intval($score));
    }

    /**
     * Calcula calidad facial
     */
    private static function calculateFaceQuality(array $data): int
    {
        $score = 0;

        // Confianza
        $score += min(50, ($data['confidence'] ?? 0) * 0.5);

        // Calidad de captura
        $score += min(30, ($data['quality'] ?? 0) * 0.3);

        // Pose (penalizar poses extremas)
        $pose = $data['pose'] ?? ['yaw' => 0, 'pitch' => 0, 'roll' => 0];
        $posePenalty = 0;
        if (abs($pose['yaw']) > 30 || abs($pose['pitch']) > 20 || abs($pose['roll']) > 15) {
            $posePenalty = 20;
        }
        $score += max(0, 20 - $posePenalty);

        return min(100, intval($score));
    }

    /**
     * Comprime datos biométricos para almacenamiento
     */
    public static function compressBiometricData(array $data): string
    {
        $json = json_encode($data);
        return gzcompress($json, 9);
    }

    /**
     * Descomprime datos biométricos
     */
    public static function decompressBiometricData(string $compressedData): array
    {
        $json = gzuncompress($compressedData);
        return json_decode($json, true);
    }

    /**
     * Genera hash para comparación rápida
     */
    public static function generateBiometricHash(array $data, string $type): string
    {
        $normalized = self::normalizeBiometricData($data, $type);
        $keyData = $type === 'huella' ? $normalized['template'] : $normalized['template'];
        return hash('sha256', $keyData);
    }

    /**
     * Compara calidad de dos sets de datos biométricos
     */
    public static function compareQuality(array $data1, array $data2, string $type): int
    {
        $quality1 = self::calculateQualityScore($data1, $type);
        $quality2 = self::calculateQualityScore($data2, $type);

        return $quality1 - $quality2; // Positivo si data1 es mejor
    }

    /**
     * Detecta si los datos biométricos parecen ser de prueba/simulación
     */
    public static function isTestData(array $data, string $type): bool
    {
        // Verificar patrones comunes de datos simulados
        if ($type === 'huella' && isset($data['template'])) {
            // Los datos simulados suelen tener templates largos con caracteres aleatorios
            if (strlen($data['template']) > 1000 && preg_match('/^[A-Za-z0-9+\/=]+$/', $data['template'])) {
                return true;
            }
        }

        if ($type === 'cara' && isset($data['template'])) {
            if (strlen($data['template']) > 2000 && preg_match('/^[A-Za-z0-9+\/=]+$/', $data['template'])) {
                return true;
            }
        }

        return false;
    }
}
