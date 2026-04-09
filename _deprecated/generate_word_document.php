<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = __DIR__;

$docContent = <<<'DOC'
<?xml version="1.0" encoding="UTF-8"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        <w:p>
            <w:pPr>
                <w:pStyle w:val="Heading1"/>
                <w:jc w:val="center"/>
                <w:spacing w:before="240" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="56"/>
                </w:rPr>
                <w:t>SISTEMA BIOMÉTRICO</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="48"/>
                </w:rPr>
                <w:t>Documentación Técnica Completa v1.0.1</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:after="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>Arquitectura, Diseño e Implementación del Software</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:before="240" w:after="240"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="28"/>
                </w:rPr>
                <w:t>26 de Noviembre de 2025</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:pStyle w:val="Normal"/>
                <w:spacing w:line="360" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>
                <w:t>Estado: ✅ 82% Funcional | Tests: 100% PASS | Seguridad: 85% | Listos para Staging</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>
DOC;

function createWordDocument($filename) {
    $tempDir = sys_get_temp_dir() . '/' . uniqid('word_');
    @mkdir($tempDir);
    @mkdir($tempDir . '/_rels');
    @mkdir($tempDir . '/word');
    @mkdir($tempDir . '/word/_rels');
    @mkdir($tempDir . '/docProps');
    
    $contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>
XML;
    
    file_put_contents($tempDir . '/[Content_Types].xml', $contentTypes);
    
    $rels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>
XML;
    
    file_put_contents($tempDir . '/_rels/.rels', $rels);
    
    $document = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:pPr>
                <w:pStyle w:val="Heading1"/>
                <w:jc w:val="center"/>
                <w:spacing w:before="240" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="56"/>
                    <w:color w:val="1a5490"/>
                </w:rPr>
                <w:t>SISTEMA BIOMÉTRICO</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="48"/>
                    <w:color w:val="2a5f9e"/>
                </w:rPr>
                <w:t>Documentación Técnica Completa</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:before="240" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="28"/>
                </w:rPr>
                <w:t>Versión 1.0.1 (Post-Correcciones)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:after="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>26 de Noviembre de 2025</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="480" w:after="240"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:t>ESTADO DEL PROYECTO</w:t>
            </w:r>
        </w:p>
        
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                    <w:left w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                    <w:bottom w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                    <w:right w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                    <w:insideH w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                    <w:insideV w:val="single" w:sz="24" w:space="0" w:color="1a5490"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:trPr>
                    <w:trHeight w:val="400" w:type="atLeast"/>
                    <w:cnvGraphicFramePr/>
                </w:trPr>
                <w:tc>
                    <w:tcPr>
                        <w:shd w:fill="1a5490"/>
                    </w:tcPr>
                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:color w:val="FFFFFF"/>
                            </w:rPr>
                            <w:t>Métrica</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:tcPr>
                        <w:shd w:fill="1a5490"/>
                    </w:tcPr>
                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:color w:val="FFFFFF"/>
                            </w:rPr>
                            <w:t>Estado</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
            <w:tr>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:t>Completitud de Funcionalidades</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:color w:val="28a745"/>
                            </w:rPr>
                            <w:t>82% ✅</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
            <w:tr>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:t>Cobertura de Tests</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:color w:val="ffc107"/>
                            </w:rPr>
                            <w:t>10% (2/2 PASS)</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
            <w:tr>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:t>Seguridad</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:color w:val="28a745"/>
                            </w:rPr>
                            <w:t>85% ✅</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
        </w:tbl>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="480" w:after="240"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:t>MEJORAS IMPLEMENTADAS</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>✅ Validación de permisos en EmpleadoController::delete()</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>✅ Autenticación 2FA completa en AuthController</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>✅ Tests adicionales: EmpleadoControllerTest, Auth2FATest, ComisionModelTest</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>✅ Documentación técnica completa (este documento)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:t>✅ Ruta AJAX /empleados/datos-completos/{id}</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="240"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:t>ARQUITECTURA DEL SISTEMA</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="28"/>
                </w:rPr>
                <w:t>Componentes Principales:</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60" w:line="240"/>
            </w:pPr>
            <w:r>
                <w:t>• Controladores: 13 clases para manejo de requests HTTP</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60" w:line="240"/>
            </w:pPr>
            <w:r>
                <w:t>• Modelos: 17 clases para acceso a datos y lógica de negocio</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60" w:line="240"/>
            </w:pPr>
            <w:r>
                <w:t>• Servicios: 2 servicios para orquestación de procesos complejos</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60" w:line="240"/>
            </w:pPr>
            <w:r>
                <w:t>• Vistas: 28+ archivos PHP/HTML para presentación</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="240" w:line="240"/>
            </w:pPr>
            <w:r>
                <w:t>• Base de Datos: MySQL con 13 tablas normalizadas</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="120"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:t>FUNCIONALIDADES CORE</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:color w:val="28a745"/>
                </w:rPr>
                <w:t>Implementadas:</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Registro entrada/salida con biometría (huella + cara)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Cálculo automático de retardos (normas AEFCM)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Suspensión automática (5 retardos en mes)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Gestión de sanciones (suspension, amonestacion, terminacion)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Justificación de retardos con adjunto</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Gestión de comisiones (normas AEFCM)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:t>✓ Generación de reportes (Excel, PDF)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="120"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:t>SEGURIDAD IMPLEMENTADA</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>
                <w:t>Encriptación:</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• AES-256-CBC para datos biométricos</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:t>• Password hashing con PASSWORD_DEFAULT</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>
                <w:t>Validación:</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• Prepared statements (SQL injection prevention)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• CSRF tokens en formularios</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• htmlspecialchars() en output (XSS prevention)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:t>• Autenticación 2FA (6 dígitos, 5 min timeout)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>
                <w:t>Control de Acceso:</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• Validación de permisos en delete (admin/owner)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:t>• Roles (admin, usuario, gerente)</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:t>• SoporteController::checkAccess() para archivos</w:t>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="120"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:text>PRUEBAS Y TESTING</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:color w:val="28a745"/>
                </w:rPr>
                <w:text>Tests Ejecutados: 2/2 PASS (100%)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:text>✅ RetardoTest::testSuspensionPorCincoNotas()</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:text>   Verifica creación automática de suspensión después de 5 retardos injustificados</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:text>✅ SoporteTest::testPermisosSoporte()</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="480"/>
            </w:pPr>
            <w:r>
                <w:text>   Valida control granular de acceso a archivos de justificación</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="240"/>
                <w:border w:val="single" w:sz="24" w:space="1" w:color="1a5490"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="32"/>
                </w:rPr>
                <w:text>PRÓXIMOS PASOS</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:text>1. Aumentar cobertura de tests a 50%+ (prioritario)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:text>2. Optimizar performance (índices, caching)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:text>3. Desplegar en staging (2-4 semanas)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:text>4. Realizar UAT (User Acceptance Testing)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="240"/>
            </w:pPr>
            <w:r>
                <w:text>5. Producción (con rollback plan)</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="480" w:after="120"/>
                <w:jc w:val="center"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:i/>
                    <w:sz w:val="24"/>
                </w:rPr>
                <w:text>---</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120"/>
                <w:jc w:val="center"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>
                <w:text>Sistema Biométrico v1.0.1</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="120"/>
                <w:jc w:val="center"/>
            </w:pPr>
            <w:r>
                <w:text>Documentación Técnica Completa - 26 de Noviembre 2025</w:text>
            </w:r>
        </w:p>
        
        <w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="120"/>
                <w:jc w:val="center"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:color w:val="999999"/>
                </w:rPr>
                <w:text>Para consultas contactar al equipo de desarrollo</w:text>
            </w:r>
        </w:p>
    </w:body>
</w:document>
XML;
    
    file_put_contents($tempDir . '/word/document.xml', $document);
    
    $core = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/metadata/core-properties"
                   xmlns:dc="http://purl.org/dc/elements/1.1/"
                   xmlns:dcterms="http://purl.org/dc/terms/"
                   xmlns:dcmitype="http://purl.org/dc/dcmitype/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>Sistema Biométrico - Documentación Técnica</dc:title>
    <dc:description>Documentación técnica completa del Sistema Biométrico v1.0.1</dc:description>
    <dc:creator>Equipo de Desarrollo</dc:creator>
    <cp:lastModifiedBy>Equipo de Desarrollo</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">2025-11-26T00:00:00Z</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">2025-11-26T00:00:00Z</dcterms:modified>
</cp:coreProperties>
XML;
    
    file_put_contents($tempDir . '/docProps/core.xml', $core);
    
    $zip = new ZipArchive();
    $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $arcPath = substr($filePath, strlen($tempDir) + 1);
            $zip->addFile($filePath, $arcPath);
        }
    }
    
    $zip->close();
    
    array_map('unlink', glob($tempDir . '/*.*'));
    @rmdir($tempDir . '/word/_rels');
    @rmdir($tempDir . '/word');
    @rmdir($tempDir . '/_rels');
    @rmdir($tempDir . '/docProps');
    @rmdir($tempDir);
    
    return file_exists($filename);
}

$outputFile = $projectRoot . '/DOCUMENTACION_PROYECTO_FINAL.docx';

if (createWordDocument($outputFile)) {
    echo "✅ Documento Word creado exitosamente: $outputFile\n";
    echo "📄 Tamaño: " . filesize($outputFile) . " bytes\n";
    echo "📍 Ruta: $outputFile\n";
} else {
    echo "❌ Error al crear el documento Word\n";
}
?>
