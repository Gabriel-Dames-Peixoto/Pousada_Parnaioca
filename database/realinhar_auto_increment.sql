USE parnaiocagabriel;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM clientes);
SET @sql = CONCAT('ALTER TABLE clientes AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM consumo_frigobar);
SET @sql = CONCAT('ALTER TABLE consumo_frigobar AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM frigobar);
SET @sql = CONCAT('ALTER TABLE frigobar AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM logs_sistema);
SET @sql = CONCAT('ALTER TABLE logs_sistema AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM permissoes);
SET @sql = CONCAT('ALTER TABLE permissoes AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM quartos);
SET @sql = CONCAT('ALTER TABLE quartos AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM reservas);
SET @sql = CONCAT('ALTER TABLE reservas AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM tipos_acomodacao);
SET @sql = CONCAT('ALTER TABLE tipos_acomodacao AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM usuarios);
SET @sql = CONCAT('ALTER TABLE usuarios AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
