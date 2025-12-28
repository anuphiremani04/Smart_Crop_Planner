CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50),
    api_token VARCHAR(128),
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_no VARCHAR(30),
    location VARCHAR(100),
    soil_type VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE crops (
    crop_id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(150) NOT NULL,
    suitable_soil VARCHAR(255),
    season VARCHAR(50),
    description TEXT,
    market_price_per_unit DECIMAL(12,2) DEFAULT 0
);

CREATE TABLE weather_data (
    weather_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100) NOT NULL,
    month TINYINT NOT NULL,
    temperature DECIMAL(6,2),
    rainfall DECIMAL(8,2),
    humidity DECIMAL(6,2),
    season VARCHAR(50)
);

CREATE TABLE user_searches (
    search_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    query_text VARCHAR(255),
    result_count INT DEFAULT 0
);

-- Sample Login Details:
-- Admin  → Username: admin  | Password: 1234567890
-- Farmer → Username: farmer | Password: 1234567890
