create database parnaiocagabriel;

CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(100) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `perfil` enum('adm','user') DEFAULT NULL,
  `status` int DEFAULT NULL,
  `nivel` tinyint NOT NULL DEFAULT '2' COMMENT '2=administrador, 1=funcionario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `idx_perfil` (`perfil`)
);

CREATE TABLE `quartos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quarto` varchar(50) NOT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `descricao` text,
  `capacidade` int DEFAULT NULL,
  `vagas_estacionamento` int DEFAULT NULL,
  `status` enum('1','0') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_quarto_tipo` (`tipo`)
);

CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `status` int DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `frigobar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `quantidade` int DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `quarto_id` int NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `status_frigobar` enum('1','0') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_quarto_frigobar` (`quarto_id`),
  CONSTRAINT `fk_quarto_frigobar` 
  FOREIGN KEY (`quarto_id`) REFERENCES `quartos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE `reservas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quarto_id` int DEFAULT NULL,
  `cliente_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `data_reserva` datetime DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `data_checkin` date DEFAULT NULL,
  `hora_checkin` time DEFAULT NULL,
  `data_checkout` date DEFAULT NULL,
  `hora_checkout` time DEFAULT NULL,
  `status` enum('ativa','cancelada','finalizada') DEFAULT 'ativa',
  `data_finalizacao` datetime DEFAULT NULL,
  `data_cancelamento` datetime DEFAULT NULL,
  `motivo_cancelamento` text,
  PRIMARY KEY (`id`),
  KEY `fk_reservas_cliente` (`cliente_id`),
  KEY `fk_reservas_quarto` (`quarto_id`),
  KEY `fk_reservas_usuario` (`usuario_id`),
  CONSTRAINT `fk_reservas_cliente` 
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reservas_quarto` 
  FOREIGN KEY (`quarto_id`) REFERENCES `quartos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reservas_usuario` 
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `logs_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `acao` varchar(20) DEFAULT NULL,
  `mensagem` text NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `consumo_frigobar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reserva_id` int NOT NULL,
  `frigobar_id` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_consumo_reserva` (`reserva_id`),
  KEY `fk_consumo_frigobar` (`frigobar_id`),
  CONSTRAINT `fk_consumo_frigobar` FOREIGN KEY (`frigobar_id`) REFERENCES `frigobar` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_consumo_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE `tipos_acomodacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nome` (`nome`)
);

CREATE TABLE `permissoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perfil` enum('adm','user') NOT NULL,
  `pagina` varchar(80) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perfil_pagina` (`perfil`,`pagina`),
  CONSTRAINT `fk_permissao_perfil` FOREIGN KEY (`perfil`) REFERENCES `usuarios` (`perfil`) ON DELETE CASCADE ON UPDATE CASCADE
);
