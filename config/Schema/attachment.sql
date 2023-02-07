-- -----------------------------------------------------
-- Table `attachment`.`atags`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachment`.`atags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `atag_type_id` INT NULL,
  `user_id` CHAR(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `slug_UNIQUE` (`slug` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 21;


-- -----------------------------------------------------
-- Table `attachment`.`attachments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachment`.`attachments` (
  `id` CHAR(36) NOT NULL,
  `profile` VARCHAR(45) NOT NULL DEFAULT 'default',
  `type` VARCHAR(45) NOT NULL,
  `subtype` VARCHAR(45) NOT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `size` BIGINT UNSIGNED NOT NULL,
  `md5` VARCHAR(32) NOT NULL,
  `path` VARCHAR(255) NULL DEFAULT NULL,
  `embed` TEXT NULL DEFAULT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `date` DATETIME NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `author` VARCHAR(255) NULL DEFAULT NULL,
  `copyright` VARCHAR(255) NULL DEFAULT NULL,
  `width` INT(10) UNSIGNED NULL DEFAULT '0',
  `height` INT(10) UNSIGNED NULL DEFAULT '0',
  `duration` INT UNSIGNED NULL,
  `meta` TEXT NULL,
  `user_id` CHAR(36) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 63;


-- -----------------------------------------------------
-- Table `attachment`.`atag_types`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachment`.`atag_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `exclusive` TINYINT(1) NOT NULL DEFAULT 0,
  `order` INT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `attachment`.`ai18n`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachment`.`ai18n` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `locale` VARCHAR(6) NOT NULL,
  `model` VARCHAR(255) NOT NULL,
  `foreign_key` CHAR(36) NOT NULL,
  `field` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fkey` (`foreign_key` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `attachment`.`attachments_atags`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachment`.`attachments_atags` (
  `attachment_id` CHAR(36) NOT NULL,
  `atag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`attachment_id`, `atag_id`),
  INDEX `fk_attachments_has_atags_atags1_idx` (`atag_id` ASC),
  INDEX `fk_attachments_has_atags_attachments_idx` (`attachment_id` ASC),
  CONSTRAINT `fk_attachments_has_atags_attachments`
    FOREIGN KEY (`attachment_id`)
    REFERENCES `attachment`.`attachments` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_attachments_has_atags_atags1`
    FOREIGN KEY (`atag_id`)
    REFERENCES `attachment`.`atags` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
