-- Migration: Add area_fisica to empleados table
-- Date: 2026-03-18
-- Description: Adds area_fisica column to store physical location of employees

ALTER TABLE empleados ADD COLUMN IF NOT EXISTS area_fisica VARCHAR(100) DEFAULT NULL AFTER area;

-- Add comment to explain the difference between area and area_fisica
-- area = nombre del departamento al que pertenece
-- area_fisica = ubicación física donde trabaja el empleado
