-- MySQL Initialization Script
-- Creates additional databases if needed

-- Create test database
CREATE DATABASE IF NOT EXISTS ecommerce_test;

-- Grant privileges
GRANT ALL PRIVILEGES ON ecommerce_platform.* TO 'ecommerce'@'%';
GRANT ALL PRIVILEGES ON ecommerce_test.* TO 'ecommerce'@'%';

FLUSH PRIVILEGES;

-- Display databases
SHOW DATABASES;
