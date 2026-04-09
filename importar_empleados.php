<?php
/**
 * Script para importar empleados desde datos externos
 * Uso: php importar_empleados.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Datos a importar (del usuario)
$empleados = [
    ['id' => 142, 'nombre' => 'GODOY NUÑEZ ALEXA MARIELLE', 'area' => 'ADMÓN ESCOLAR', 'clave_depto' => 'DAE'],
    ['id' => 519, 'nombre' => 'ORTEGA LOPEZ CARMEN ALEJANDRA', 'area' => 'ADMÓN ESCOLAR', 'clave_depto' => 'DAE'],
    ['id' => 150, 'nombre' => 'PANDO ACOSTA CHRISTIAN', 'area' => 'ADMÓN ESCOLAR', 'clave_depto' => 'DAE'],
    ['id' => 961, 'nombre' => 'REZA MORALES OSCAR DANIEL', 'area' => 'ADMÓN ESCOLAR', 'clave_depto' => 'DAE'],
    ['id' => 1, 'nombre' => 'BONILLA ZAMITIZ GREGORIO', 'area' => 'ARCHIVO DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 896, 'nombre' => 'GARCIA RAMOS AICO BERENICE', 'area' => 'ARCHIVO DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 351, 'nombre' => 'DOMINGUEZ MUNIVE FRANCISCO', 'area' => 'CM CARLOS A CARRILLO', 'clave_depto' => 'JDRMS-B'],
    ['id' => 350, 'nombre' => 'GARCIA MEJIA HUMBERTO', 'area' => 'CM CARLOS A CARRILLO', 'clave_depto' => 'DATEDPD'],
    ['id' => 352, 'nombre' => 'MIRANDA LEON EMMANUEL', 'area' => 'CM CARLOS A CARRILLO', 'clave_depto' => 'DATEDPD'],
    ['id' => 349, 'nombre' => 'VEGA BLANCO IXCHEL', 'area' => 'CM CARLOS A CARRILLO', 'clave_depto' => 'DATEDPD'],
    ['id' => 591, 'nombre' => 'ZETA MORALES MARCO ANTONIO', 'area' => 'CM CARLOS A CARRILLO', 'clave_depto' => 'JDRMS-B'],
    ['id' => 177, 'nombre' => 'ALVAREZ AGUILAR GUILLERMO', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'DATEDPD'],
    ['id' => 176, 'nombre' => 'BUSTOS GARCIA TANIA NATALIE', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'DATEDPD'],
    ['id' => 175, 'nombre' => 'COLIN REZA GERARDO', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'DATEDPD'],
    ['id' => 656, 'nombre' => 'CRUZ VARGAS IVAN', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'JDRMS-B'],
    ['id' => 6, 'nombre' => 'GARCIA NUÑEZ LILIAN AMERICA', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'JDRMS-B'],
    ['id' => 14, 'nombre' => 'GARCIA RAMIREZ MARCOS FCO', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'DATEDPD'],
    ['id' => 169, 'nombre' => 'GONZALEZ GUZMAN MARTHA', 'area' => 'CM ERMILO ABREU', 'clave_depto' => 'DATEDPD'],
    ['id' => 258, 'nombre' => 'CARRILLO ZAMARRIPA VICTOR', 'area' => 'CM LUIS ALVAREZ BARRET', 'clave_depto' => 'DATEDPD'],
    ['id' => 325, 'nombre' => 'CORONA CARRION FABIO M.', 'area' => 'CM LUIS ALVAREZ BARRET', 'clave_depto' => 'DATEDPD'],
    ['id' => 625, 'nombre' => 'GARCÍA CELAYO JANET', 'area' => 'CM LUIS ALVAREZ BARRET', 'clave_depto' => 'JDRMS-B'],
    ['id' => 578, 'nombre' => 'PEREZ VARGAS LAURA ELIZABETH', 'area' => 'CM LUIS ALVAREZ BARRET', 'clave_depto' => 'JDRMS-B'],
    ['id' => 409, 'nombre' => 'BARBARA SALAZAR ANA M.', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 593, 'nombre' => 'GORDILLO CEDEÑO MIRNA LUZ', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 103, 'nombre' => 'HERNÁNDEZ MEJIA ARTURO', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 3, 'nombre' => 'RAMIREZ RAYMUNDO RODOLFO', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 102, 'nombre' => 'RICO MEDRANO MA. GUADALUPE', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 115, 'nombre' => 'SALGADO ALVARADO FELIPE DE JESUS', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 104, 'nombre' => 'TORRES SOTO MA. FERNANDA', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 122, 'nombre' => 'ZAMORA PEREZ VICTOR HUGO', 'area' => 'CM RAMIRO REYES ESPARZA', 'clave_depto' => 'DATEDPD'],
    ['id' => 1620, 'nombre' => 'CHINO MARTINEZ ANGELICA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 2503, 'nombre' => 'FUENTES GUTIERREZ SALVADOR', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPPDA'],
    ['id' => 55, 'nombre' => 'GARCIA FLORES CARLOS GABRIEL', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 46, 'nombre' => 'HERNANDEZ VALLEJO TANYA YANIN', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 90, 'nombre' => 'MAGAÑA MARTINEZ PAULINA S.', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPPDA'],
    ['id' => 957, 'nombre' => 'MOCTEZUMA MENDOZA ANDREA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 93, 'nombre' => 'MORALES MENDOZA EMMA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 643, 'nombre' => 'NUÑEZ HERNÁNDEZ SONIA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 1607, 'nombre' => 'POLANCO TAPIA ANA LAURA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 72, 'nombre' => 'ROMERO SALAZAR JONATHAN AXEL', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 1819, 'nombre' => 'SALGADO FRANCO LESSLY J', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 1818, 'nombre' => 'VARGAS BRISENO MARIANA', 'area' => 'COMISIONADO EN PLANEACION', 'clave_depto' => 'DPE'],
    ['id' => 45, 'nombre' => 'ARREOLA REYES PORFIRIO', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 541, 'nombre' => 'HIJAR ZEPEDA ANAID ALEJANDRA', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 128, 'nombre' => 'MIRANDA MORENO CLAUDIA', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 418, 'nombre' => 'PONCE AGUILERA ANABEL', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 77, 'nombre' => 'SALVADOR HERNANDEZ ANA LILIA', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 522, 'nombre' => 'SANTIBAÑEZ ALONSO JESSICA JANETTE', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 24, 'nombre' => 'TAPIA GALICIA JOSE DE JESUS', 'area' => 'COORDINAC ADMIVA ERMILO A', 'clave_depto' => 'JDRMS-B'],
    ['id' => 547, 'nombre' => 'HERNÁNDEZ GONZÁLEZ JESÚS SALVADOR', 'area' => 'COORDINACION CM RAMIRO REYES', 'clave_depto' => 'JDRMS-B'],
    ['id' => 81, 'nombre' => 'ANGELES GORDILLO KEVIN ABELARDO', 'area' => 'DIRECCIÓN GENERAL', 'clave_depto' => 'DGPPEE'],
    ['id' => 82, 'nombre' => 'FERRARA GUTIERREZ ABRIL', 'area' => 'DIRECCIÓN GENERAL', 'clave_depto' => 'DGPPEE'],
    ['id' => 83, 'nombre' => 'MANRIQUEZ RODRIGUEZ GABRIELA', 'area' => 'DIRECCIÓN GENERAL', 'clave_depto' => 'DGPPEE'],
    ['id' => 803, 'nombre' => 'CRUZ RODRIGUEZ DANIELA', 'area' => 'DISEÑO', 'clave_depto' => 'DISEÑO'],
    ['id' => 802, 'nombre' => 'HERNANDEZ ROSAS ITZEL', 'area' => 'DISEÑO', 'clave_depto' => 'DISEÑO'],
    ['id' => 74, 'nombre' => 'LOPEZ LOPEZ KAREN AILIN', 'area' => 'DISEÑO', 'clave_depto' => 'DISEÑO'],
    ['id' => 524, 'nombre' => 'ROMERO ZUNIGA PAOLA', 'area' => 'DISEÑO', 'clave_depto' => 'DISEÑO'],
    ['id' => 96, 'nombre' => 'VALENCIA SOSA DALIA', 'area' => 'DISEÑO', 'clave_depto' => 'DISEÑO'],
    ['id' => 146, 'nombre' => 'ALVAREZ FLORES HECTOR FRANCISCO', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 536, 'nombre' => 'AVENDAÑO SANCHEZ IVONNE YANIRA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 293, 'nombre' => 'BAÑOS ENCISO MARTHA LUZ', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 70, 'nombre' => 'CASIANO CRUZ YURIANA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 634, 'nombre' => 'CASTILLO GARCÍA JESÚS', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 419, 'nombre' => 'CONTRERAS ARELLANO ANTONIO', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 632, 'nombre' => 'ESQUIVEL SOTO JAVIER ANTONIO', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 62, 'nombre' => 'FLORES AMADOR NORA LUISA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 242, 'nombre' => 'FLORES LOPEZ GUADALUPE', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 210, 'nombre' => 'LÓPEZ GÓMEZ BLANCA ESTELA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 69, 'nombre' => 'LOPEZ HERNANDEZ IRMA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 670, 'nombre' => 'MARTINEZ VELEZ GABRIELA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 38, 'nombre' => 'PADILLA SOLARES SERGIO', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 157, 'nombre' => 'PEREA HERNANDEZ EDITH', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 39, 'nombre' => 'RIVERA TIRADO GENARO ANDRES', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 513, 'nombre' => 'SECEÑA DIAZ ERIKA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 208, 'nombre' => 'SOLIS GUTIERREZ ROSALIA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 263, 'nombre' => 'TREJO MONTESILLO YAZMIN LILIANA', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 512, 'nombre' => 'VILLARREAL ENCARNACION JONATHAN RODOLFO', 'area' => 'NEZAHUALCOYOTL DGIFA', 'clave_depto' => 'JDRMS-B'],
    ['id' => 202, 'nombre' => 'CHAVEZ JAUREGUI CESAR ISAIAS', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 119, 'nombre' => 'GARCIA ANDERSON PATRICIA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 80, 'nombre' => 'MENDOZA MENDIETA LIDIA LUCIA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 166, 'nombre' => 'ORTEGA RAMIREZ DANIEL', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 167, 'nombre' => 'ORTIZ GARDUÑO ALMA DELIA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 539, 'nombre' => 'ORTIZ VÁZQUEZ MARIO ALBERTO', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 20, 'nombre' => 'RENDON SANCHEZ MARIA MARINA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 57, 'nombre' => 'RODRÍGUEZ GAONA GLORIA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 654, 'nombre' => 'SÁNCHEZ YAÑEZ MARTHA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 141, 'nombre' => 'SOLANO GOMEZ ANA KARINA', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 27, 'nombre' => 'ULLOA GONZÁLEZ MIGUEL IGNACIO', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
    ['id' => 267, 'nombre' => 'VIRGEN TRUJILLO CARLOS CANUTO', 'area' => 'UNIDAD DE TRANSPARENCIA Y AUDI', 'clave_depto' => 'UTyA'],
];

$inserted = 0;
$updated = 0;
$errors = [];

// Función para separar nombre completo (formato mexicano: APELLIDO PATERNO APELLIDO MATERNO NOMBRE(S))
function separarNombre($nombreCompleto) {
    $nombreCompleto = trim($nombreCompleto);
    $partes = explode(' ', $nombreCompleto);
    $total = count($partes);
    
    if ($total == 1) {
        return ['nombre' => $nombreCompleto, 'apellido' => ''];
    }
    
    if ($total == 2) {
        // 2 partes: "APELLIDO NOMBRE"
        return ['nombre' => $partes[1], 'apellido' => $partes[0]];
    }
    
    if ($total == 3) {
        // 3 partes: "APELLIDO1 APELLIDO2 NOMBRE"
        return ['nombre' => $partes[2], 'apellido' => $partes[0] . ' ' . $partes[1]];
    }
    
    // 4+ partes: "APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2..."
    // Las primeras 2 palabras son apellidos, el resto es nombre
    $apellido = $partes[0] . ' ' . $partes[1];
    $nombre = implode(' ', array_slice($partes, 2));
    
    return ['nombre' => $nombre, 'apellido' => $apellido];
}

$pdo->beginTransaction();

foreach ($empleados as $emp) {
    // Separar nombre y apellido (formato mexicano: APELLIDO APELLIDO NOMBRE)
    $separado = separarNombre($emp['nombre']);
    $nombre = $separado['nombre'];
    $apellido = $separado['apellido'];
    
    try {
        // Verificar si existe
        $stmt = $pdo->prepare("SELECT id FROM empleados WHERE id = ?");
        $stmt->execute([$emp['id']]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE empleados SET 
                    nombre = ?, 
                    apellido = ?, 
                    area = ?, 
                    clave_depto = ?,
                    activo = 1
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $apellido, $emp['area'], $emp['clave_depto'], $emp['id']]);
            $updated++;
        } else {
            // INSERT (RFC null para evitar duplicados)
            $stmt = $pdo->prepare("
                INSERT INTO empleados (id, nombre, apellido, area, jerarquia, clave_depto, activo)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$emp['id'], $nombre, $apellido, $emp['area'], 'Empleado', $emp['clave_depto']]);
            $inserted++;
        }
    } catch (Exception $e) {
        $errors[] = "Error con ID {$emp['id']}: " . $e->getMessage();
    }
}

$pdo->commit();

echo "Importación completada.\n";
echo "Insertados: $inserted\n";
echo "Actualizados: $updated\n";
if (!empty($errors)) {
    echo "Errores:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}
