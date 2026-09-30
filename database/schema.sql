CREATE DATABASE IF NOT EXISTS shamba_guide DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shamba_guide;

-- 1. Role Table
CREATE TABLE IF NOT EXISTS Role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description VARCHAR(255)
);

-- 2. User Table (escaped with backticks for MySQL keyword protection)
CREATE TABLE IF NOT EXISTS `User` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    region VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES Role(role_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 3. Crop Table
CREATE TABLE IF NOT EXISTS Crop (
    crop_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    growing_season VARCHAR(100),
    region VARCHAR(100)
);

-- 4. Query Log Table (Advisory requests submitted by Farmers)
CREATE TABLE IF NOT EXISTS Query_Log (
    query_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query_text TEXT NOT NULL,
    response_text TEXT,
    language ENUM('Swahili', 'English') DEFAULT 'Swahili',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 5. Planting Calendar Table (Seasonal recommendations per crop)
CREATE TABLE IF NOT EXISTS Planting_Calendar (
    calendar_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    planting_month VARCHAR(50) NOT NULL,
    harvest_month VARCHAR(50) NOT NULL,
    recommended_activities TEXT,
    FOREIGN KEY (crop_id) REFERENCES Crop(crop_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 6. Pest Table (Common pests affecting crops)
CREATE TABLE IF NOT EXISTS Pest (
    pest_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    pest_name VARCHAR(100) NOT NULL,
    symptoms TEXT,
    control_measures TEXT,
    FOREIGN KEY (crop_id) REFERENCES Crop(crop_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 7. Disease Table (Crop diseases and treatment strategies)
CREATE TABLE IF NOT EXISTS Disease (
    disease_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    disease_name VARCHAR(100) NOT NULL,
    symptoms TEXT,
    prevention_measures TEXT,
    FOREIGN KEY (crop_id) REFERENCES Crop(crop_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Seed Default Roles
INSERT INTO Role (role_id, role_name, role_description) VALUES 
(1, 'Farmer', 'Smallholder farmer accessing advisory services'),
(2, 'Extension Officer', 'Agricultural officer managing queries and field data'),
(3, 'Admin', 'System administrator maintaining system knowledge')
ON DUPLICATE KEY UPDATE role_name=VALUES(role_name);