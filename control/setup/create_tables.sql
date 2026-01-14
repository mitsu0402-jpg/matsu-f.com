CREATE DATABASE IF NOT EXISTS `_matsunagafu`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `_matsunagafu`;

-- Admin users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `sort` INT NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Sale properties
CREATE TABLE IF NOT EXISTS `sale_properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `price` INT UNSIGNED NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `area_id` INT UNSIGNED NULL,
  `layout` VARCHAR(50) NULL,
  `land_area` DECIMAL(10,2) NULL,
  `building_area` DECIMAL(10,2) NULL,
  `year_built` VARCHAR(10) NULL,
  `access` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_status` (`status`),
  KEY `idx_sale_area` (`area_id`),
  KEY `idx_sale_price` (`price`),
  CONSTRAINT `fk_sale_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Rent properties
CREATE TABLE IF NOT EXISTS `rent_properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `rent` INT UNSIGNED NOT NULL,
  `management_fee` INT UNSIGNED NULL,
  `deposit` INT UNSIGNED NULL,
  `key_money` INT UNSIGNED NULL,
  `address` VARCHAR(255) NOT NULL,
  `area_id` INT UNSIGNED NULL,
  `layout` VARCHAR(50) NULL,
  `building_area` DECIMAL(10,2) NULL,
  `year_built` VARCHAR(10) NULL,
  `access` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rent_status` (`status`),
  KEY `idx_rent_area` (`area_id`),
  KEY `idx_rent_rent` (`rent`),
  CONSTRAINT `fk_rent_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Property images (shared)
CREATE TABLE IF NOT EXISTS `property_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_type` ENUM('sale','rent') NOT NULL,
  `property_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `sort` INT NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_img_property` (`property_type`, `property_id`)
) ENGINE=InnoDB;
