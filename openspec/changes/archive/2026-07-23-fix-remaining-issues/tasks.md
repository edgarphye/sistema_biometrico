# Tasks - Fix Remaining Issues

## 1. Data Migration Scripts
- [x] 1.1 Create SQL script for hierarchy assignment (jefes directos)
- [x] 1.2 Create SQL script for biometric linking (zkteo_id)
- [x] 1.3 Create SQL script for retardo migration (horario_id)
- [x] 1.4 Create SQL script for attendance validation

## 2. Database Migrations
- [x] 2.1 Backup database before migrations
- [x] 2.2 Execute hierarchy assignment script
- [x] 2.3 Execute biometric linking script
- [x] 2.4 Execute retardo migration script
- [x] 2.5 Execute attendance validation script
- [x] 2.6 Verify data integrity post-migration

## 3. Controller Fixes
- [x] 3.1 Fix MenuConfigController (extends BaseController)
- [x] 3.2 Fix remaining require paths in controllers
- [x] 3.3 Add rate limiting to public endpoints
- [x] 3.4 Improve input validation

## 4. Model Updates
- [x] 4.1 Update Empleado model validation (zkteo_id, jefe_directo_id required)
- [x] 4.2 Update Retardo model to handle horario_id
- [x] 4.3 Add integrity checks

## 5. Service Updates
- [x] 5.1 Update ZKTecoAsistenciaInserterFinal for missing horario_id
- [x] 5.2 Update validation services for new requirements
- [x] 5.3 Add migration service

## 6. Testing
- [x] 6.1 Test hierarchy assignment
- [x] 6.2 Test biometric linking
- [x] 6.3 Test retardo migration
- [x] 6.4 Test attendance validation
- [x] 6.5 Test controller fixes

## 7. Documentation
- [x] 7.1 Update API documentation
- [x] 7.2 Update migration guide
- [x] 7.3 Update user manual
