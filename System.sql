-- ==========================================
-- SYSTEM MANAGEMENT MODULE
-- Database: health_monitoring
-- ==========================================

USE health_monitoring;


-- ==========================================
-- TOTAL ADMINISTRATORS
-- ==========================================

SELECT COUNT(*) AS total_admins
FROM admin;


-- ==========================================
-- TOTAL CLIENTS / USERS
-- ==========================================

SELECT COUNT(*) AS total_clients
FROM client;


-- ==========================================
-- TOTAL DOCTORS
-- ==========================================

SELECT COUNT(*) AS total_doctors
FROM doctor;


-- ==========================================
-- TOTAL PATIENTS
-- ==========================================

SELECT COUNT(*) AS total_patients
FROM patient;


-- ==========================================
-- TOTAL APPOINTMENTS
-- ==========================================

SELECT COUNT(*) AS total_appointments
FROM appointments;


-- ==========================================
-- TOTAL HEALTH RECORDS
-- ==========================================

SELECT COUNT(*) AS total_health_records
FROM health_record;


-- ==========================================
-- TOTAL REPORTS
-- ==========================================

SELECT COUNT(*) AS total_reports
FROM report;


-- ==========================================
-- TOTAL MEDICINES
-- ==========================================

SELECT COUNT(*) AS total_medicines
FROM prescribed_medicine;


-- ==========================================
-- COMPLETE SYSTEM STATISTICS
-- ==========================================

SELECT
    (SELECT COUNT(*) FROM admin) AS total_admins,
    (SELECT COUNT(*) FROM client) AS total_clients,
    (SELECT COUNT(*) FROM doctor) AS total_doctors,
    (SELECT COUNT(*) FROM patient) AS total_patients,
    (SELECT COUNT(*) FROM appointments) AS total_appointments,
    (SELECT COUNT(*) FROM health_record) AS total_health_records,
    (SELECT COUNT(*) FROM report) AS total_reports,
    (SELECT COUNT(*) FROM prescribed_medicine) AS total_medicines;