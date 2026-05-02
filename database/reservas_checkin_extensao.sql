USE parnaiocagabriel;

SET @coluna_existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'parnaiocagabriel'
      AND TABLE_NAME = 'reservas'
      AND COLUMN_NAME = 'data_checkin_real'
);
SET @sql = IF(
    @coluna_existe = 0,
    'ALTER TABLE reservas ADD COLUMN data_checkin_real DATETIME DEFAULT NULL AFTER hora_checkin',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @coluna_existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'parnaiocagabriel'
      AND TABLE_NAME = 'reservas'
      AND COLUMN_NAME = 'data_ultima_extensao'
);
SET @sql = IF(
    @coluna_existe = 0,
    'ALTER TABLE reservas ADD COLUMN data_ultima_extensao DATETIME DEFAULT NULL AFTER hora_checkout',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
