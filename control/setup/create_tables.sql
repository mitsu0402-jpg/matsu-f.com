CREATE DATABASE IF NOT EXISTS `_matsunagafu`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `_matsunagafu`;

-- 管理ユーザー
CREATE TABLE IF NOT EXISTS `users` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `名称` VARCHAR(100) NOT NULL,
  `メール` VARCHAR(255) NOT NULL UNIQUE,
  `パスワード_hash` VARCHAR(255) NOT NULL,
  `公開状態` TINYINT NOT NULL DEFAULT 1,
  `created_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP,
  `up日付d_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP ON UP日付 CUR賃料_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB;

-- エリアs
CREATE TABLE IF NOT EXISTS `エリアs` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `名称` VARCHAR(100) NOT NULL,
  `並び順` INT NOT NULL DEFAULT 0,
  `公開状態` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB;

-- 売り物件
CREATE TABLE IF NOT EXISTS `sale_properties` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `タイトル` VARCHAR(255) NOT NULL,
  `価格` INT UNSIGNED NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `エリア_ID` INT UNSIGNED NULL,
  `間取り` VARCHAR(50) NULL,
  `land_エリア` DECIMAL(10,2) NULL,
  `building_エリア` DECIMAL(10,2) NULL,
  `year_built` VARCHAR(10) NULL,
  `交通` VARCHAR(255) NULL,
  `説明` TEXT NULL,
  `公開状態` TINYINT NOT NULL DEFAULT 1,
  `created_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP,
  `up日付d_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP ON UP日付 CUR賃料_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `IDx_sale_公開状態` (`公開状態`),
  KEY `IDx_sale_エリア` (`エリア_ID`),
  KEY `IDx_sale_価格` (`価格`),
  CONSTRAINT `fk_sale_エリア` FOREIGN KEY (`エリア_ID`) REFERENCES `エリアs` (`ID`)
    ON UP日付 CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- 賃料 properties
CREATE TABLE IF NOT EXISTS `賃料_properties` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `タイトル` VARCHAR(255) NOT NULL,
  `賃料` INT UNSIGNED NOT NULL,
  `management_fee` INT UNSIGNED NULL,
  `敷金` INT UNSIGNED NULL,
  `key_money` INT UNSIGNED NULL,
  `address` VARCHAR(255) NOT NULL,
  `エリア_ID` INT UNSIGNED NULL,
  `間取り` VARCHAR(50) NULL,
  `building_エリア` DECIMAL(10,2) NULL,
  `year_built` VARCHAR(10) NULL,
  `交通` VARCHAR(255) NULL,
  `説明` TEXT NULL,
  `公開状態` TINYINT NOT NULL DEFAULT 1,
  `created_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP,
  `up日付d_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP ON UP日付 CUR賃料_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `IDx_賃料_公開状態` (`公開状態`),
  KEY `IDx_賃料_エリア` (`エリア_ID`),
  KEY `IDx_賃料_賃料` (`賃料`),
  CONSTRAINT `fk_賃料_エリア` FOREIGN KEY (`エリア_ID`) REFERENCES `エリアs` (`ID`)
    ON UP日付 CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Property 画像 (shared)
CREATE TABLE IF NOT EXISTS `property_画像` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_種別` ENUM('sale','賃料') NOT NULL,
  `property_ID` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `並び順` INT NOT NULL DEFAULT 0,
  `公開状態` TINYINT NOT NULL DEFAULT 1,
  `created_at` 日付TIME NOT NULL DEFAULT CUR賃料_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `IDx_img_property` (`property_種別`, `property_ID`)
) ENGINE=InnoDB;

