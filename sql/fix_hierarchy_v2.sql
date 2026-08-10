-- Migration: Assign jefe_directo_id to remaining 343 employees
-- Strategy: Use clave_depto and catalogos_mandos hierarchy

-- ============================================================
-- STEP 1: Assign Director General as top-level boss (id=81)
-- All directors and subdirectors report to Director General
-- ============================================================

-- Directors (report to Director General id=81)
UPDATE empleados SET jefe_directo_id = 81 WHERE id = 5064; -- VANESSA JUAREZ BELTRAN - DIRECCION CONVIVENCIA
UPDATE empleados SET jefe_directo_id = 81 WHERE id = 5073; -- TERESA MELENDEZ IRIGOYEN - DIRECCION PROGRAMAS INNOVACION

-- Subdirectors (report to their Director)
-- DCPSGR subdirectors -> Director 5064
UPDATE empleados SET jefe_directo_id = 5064 WHERE id = 5044; -- ACEVEDO ZURITA - SUBD PARTICIPACION SOCIAL
UPDATE empleados SET jefe_directo_id = 5064 WHERE id = 5047; -- LAMADRID PALOMARES - SUBD GESTION RIESGO
UPDATE empleados SET jefe_directo_id = 5064 WHERE id = 5048; -- ORTEGA SANCHEZ - SUBD CONVIVENCIA

-- DPIEP subdirectors -> Director 5073
UPDATE empleados SET jefe_directo_id = 5073 WHERE id = 5071; -- ALVAREZ CHAVEZ - SUBD OPERACION PROGRAMAS INNOVACION
UPDATE empleados SET jefe_directo_id = 5073 WHERE id = 5072; -- GOMEZ SANCHEZ - SUBD EVALUACION
UPDATE empleados SET jefe_directo_id = 5073 WHERE id = 5074; -- OLMOS SANCHEZ - SUBD DESARROLLO CURRICULAR

-- Jefes (report to their Subdirector or Director)
-- JDE -> DPIEP jefe, report to Subdirector Evaluacion 5072
UPDATE empleados SET jefe_directo_id = 5072 WHERE id = 5076; -- SANCHEZ GASCA - JDE
-- JDSPIEyP -> DPIEP, report to Subdirector OPERACION 5071
UPDATE empleados SET jefe_directo_id = 5071 WHERE id = 5075; -- OROZCO URIBE - JDSPIEyP

-- JDEA -> DCPSGR, report to Director 5064
UPDATE empleados SET jefe_directo_id = 5064 WHERE id = 5049; -- RAMIREZ DANTEZ - JDEA
-- JDP -> DCPSGR, report to SUBD CONVIVENCIA 5048
UPDATE empleados SET jefe_directo_id = 5048 WHERE id = 5050; -- SAMANO MORALES - JDP
-- JDPS -> DCPSGR, report to SUBD PARTICIPACION 5044
UPDATE empleados SET jefe_directo_id = 5044 WHERE id = 5063; -- CASIMIRO BARRAGAN - JDPS

-- ============================================================
-- STEP 2: Assign regular employees to their Jefe de Depto
-- based on clave_depto matching
-- ============================================================

-- DCPSGR employees -> Jefe RAMIREZ DANTEZ (5049) for DEPT OPERACION
-- or SAMANO MORALES (5050) for DEPT PREVENCION
-- or CASIMIRO BARRAGAN (5063) for DEPT PARTICIPACION

-- Employees with clave_depto = DCPSGR and area containing CONVIVENCIA/PARTICIPACION/GESTION
-- These report to the subdirector level (SC=5048, SPS=5044, SGR=5047)
UPDATE empleados SET jefe_directo_id = 5048 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DCPSGR' 
AND (area LIKE '%CONVIVENCIA%' OR area LIKE '%CONVIVENCIA%');

UPDATE empleados SET jefe_directo_id = 5044 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DCPSGR' 
AND area LIKE '%PARTICIPACIÓN SOCIAL%';

UPDATE empleados SET jefe_directo_id = 5047 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DCPSGR' 
AND area LIKE '%GESTIÓN DEL RIESGO%';

-- Default DCPSGR employees without specific area match
UPDATE empleados SET jefe_directo_id = 5064 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DCPSGR';

-- DPIEP employees -> Subdirectors
UPDATE empleados SET jefe_directo_id = 5072 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DPIEP' 
AND area LIKE '%EVALUACIÓN%';

UPDATE empleados SET jefe_directo_id = 5074 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DPIEP' 
AND area LIKE '%DESARROLLO CURRICULAR%';

UPDATE empleados SET jefe_directo_id = 5071 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DPIEP' 
AND (area LIKE '%INNOVACIÓN%' OR area LIKE '%PROGRAMAS%');

-- Default DPIEP employees
UPDATE empleados SET jefe_directo_id = 5073 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DPIEP';

-- DFACE Director -> id 5065 (ANTONIO DE LA CRUZ ANA LAURA)
UPDATE empleados SET jefe_directo_id = 81 WHERE id = 5065; -- DFACE Director reports to DG

-- DFACE Subdirectors -> report to DFACE Director 5065
UPDATE empleados SET jefe_directo_id = 5065 WHERE id = 5066; -- AVILA MORALES - SUBD VINCULACION
UPDATE empleados SET jefe_directo_id = 5065 WHERE id = 5068; -- TLALMIS JIMENEZ - SUBD INVESTIGACION
UPDATE empleados SET jefe_directo_id = 5065 WHERE id = 5070; -- ZUÑIGA ACEVEDO - SUBD FORTALECIMIENTO PED

-- DFACE Jefes -> report to their Subdirector
UPDATE empleados SET jefe_directo_id = 5067 WHERE id = 5069; -- TORRES MENDEZ - JDMDyRP reports to RUIZ ESPINO (Dept Planeacion)

-- DFACE regular employees -> report to DFACE Director 5065
UPDATE empleados SET jefe_directo_id = 5065 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DFACE';

-- DATE Director -> id 5001 (CAZARES VILLA MAGDALENA)
UPDATE empleados SET jefe_directo_id = 81 WHERE id = 5001; -- DATE Director reports to DG

-- DATE Subdirectors -> report to DATE Director 5001
UPDATE empleados SET jefe_directo_id = 5001 WHERE id = 5042; -- SANCHEZ PACHECO - SUBD PROGRAMAS DESARROLLO
UPDATE empleados SET jefe_directo_id = 5001 WHERE id = 5043; -- TAPIA FIGUEROA - SUBD ASISTENCIA TECNICA

-- DATE employees -> report to DATE Director 5001
UPDATE empleados SET jefe_directo_id = 5001 
WHERE jefe_directo_id IS NULL AND clave_depto = 'DATE';

-- JDRH (Recursos Humanos) employees -> Jefe 30
UPDATE empleados SET jefe_directo_id = 30 
WHERE jefe_directo_id IS NULL AND clave_depto = 'JDRH';

-- Employees with coded areas (like 073301A01E06000100152) -> Director General
UPDATE empleados SET jefe_directo_id = 81 
WHERE jefe_directo_id IS NULL AND area REGEXP '^0[0-9]';

-- Employees with DGIFA area (no clave_depto) -> Director General for now
UPDATE empleados SET jefe_directo_id = 81 
WHERE jefe_directo_id IS NULL AND area = 'DGIFA';

-- Employees with NULL area -> Director General
UPDATE empleados SET jefe_directo_id = 81 
WHERE jefe_directo_id IS NULL AND area IS NULL;

-- ============================================================
-- STEP 3: Remaining employees -> Director General
-- ============================================================
UPDATE empleados SET jefe_directo_id = 81 
WHERE jefe_directo_id IS NULL AND activo = 1;

-- ============================================================
-- VERIFICATION
-- ============================================================
SELECT 
    (SELECT COUNT(*) FROM empleados WHERE activo = 1) AS total_activos,
    (SELECT COUNT(*) FROM empleados WHERE activo = 1 AND jefe_directo_id IS NOT NULL) AS con_jefe,
    (SELECT COUNT(*) FROM empleados WHERE activo = 1 AND jefe_directo_id IS NULL) AS sin_jefe;
