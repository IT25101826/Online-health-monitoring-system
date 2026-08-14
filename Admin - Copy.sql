-- ==========================================
-- ADMINISTRATION MODULE
-- Database: health_monitoring
-- ==========================================

USE health_monitoring;

-- Create Admin table
CREATE TABLE IF NOT EXISTS admin (
    admin_id VARCHAR(10) NOT NULL,
    admin_name VARCHAR(20) NOT NULL,
    admin_address VARCHAR(30) NOT NULL,
    date_of_birth DATE NOT NULL,
    PRIMARY KEY (admin_id)
);

-- Insert sample administrator
INSERT INTO admin
(admin_id, admin_name, admin_address, date_of_birth)
VALUES
('A001', 'Admin One', 'Colombo', '1995-05-10');

-- View all administrators
SELECT *
FROM admin;

-- Count administrators
SELECT COUNT(*) AS total_admins
FROM admin;