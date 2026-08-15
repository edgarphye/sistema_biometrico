# notas-malas (delta)

## Purpose
Delta de la capacidad `notas-malas` para mostrar el estado de entrega del oficio en la tarjeta del empleado.

## ADDED Requirements

### Requirement: Acciones de entrega en la tarjeta del empleado
El sistema SHALL mostrar en la tarjeta de cada empleado del módulo de notas malas las acciones de entrega del oficio según su estado: botón "Marcar entregado" cuando el oficio existe y no está entregado, y badge "Entregado" con fecha más botón "Deshacer entrega" cuando sí está entregado.

#### Scenario: Tarjeta con oficio sin entregar
- **WHEN** el empleado tiene oficio generado con `entregado = 0`
- **THEN** la tarjeta muestra el botón "Marcar entregado"

#### Scenario: Tarjeta con oficio entregado
- **WHEN** el empleado tiene oficio generado con `entregado = 1`
- **THEN** la tarjeta muestra el badge "Entregado" con la fecha de entrega y el botón "Deshacer entrega"

#### Scenario: Sin oficio generado
- **WHEN** el empleado no tiene oficio generado
- **THEN** la tarjeta no muestra acciones de entrega
