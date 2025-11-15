-- Resources Table for Campus Crisis Platform
-- Run this in phpMyAdmin after running the main schema.sql

USE campus_crisis;

-- Campus Resources table for admin-editable resource inventory
CREATE TABLE IF NOT EXISTS resources (
  resource_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(80) NOT NULL,
  status VARCHAR(80) NOT NULL,
  quantity INT DEFAULT NULL,
  unit VARCHAR(40) DEFAULT NULL,
  is_available TINYINT(1) DEFAULT 1,
  notes TEXT DEFAULT NULL,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by VARCHAR(120) DEFAULT NULL
);

-- Insert some sample data for testing
INSERT INTO resources (name, category, status, quantity, unit, is_available, notes, updated_by) VALUES
('Emergency Generator', 'Power', 'Available', 2, 'units', 1, 'Located in basement power room', 'admin'),
('First Aid Kits', 'Medical', 'Available', 15, 'kits', 1, 'Distributed across all floors', 'admin'),
('Water Bottles', 'Water', 'Low Stock', 50, 'bottles', 1, 'Need to reorder soon', 'admin'),
('Emergency Food Rations', 'Food', 'Available', 200, 'packs', 1, 'Stored in cafeteria storage', 'admin'),
('Backup Communication Radio', 'Communication', 'Maintenance', 1, 'unit', 0, 'Under repair - expected back next week', 'admin');
