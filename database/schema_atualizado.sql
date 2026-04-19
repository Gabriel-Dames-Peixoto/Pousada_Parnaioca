CREATE DATABASE IF NOT EXISTS parnaiocagabriel;
USE parnaiocagabriel;

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(32) NOT NULL,
    perfil ENUM('adm', 'user') NOT NULL,
    status VARCHAR(255) DEFAULT '1',
    nivel TINYINT NOT NULL DEFAULT '2' COMMENT '2=administrador, 1=funcionario'
);

CREATE TABLE quartos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quarto VARCHAR(50) NOT NULL,
    tipo VARCHAR(50),
    preco DECIMAL(10, 2),
    descricao TEXT,
    capacidade INT,
    vagas_estacionamento INT,
    status ENUM('1', '0') NOT NULL DEFAULT '1'
);

CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100),
    data_nascimento DATE,
    cpf VARCHAR(14),
    email VARCHAR(100),
    telefone VARCHAR(20),
    estado VARCHAR(100),
    cidade VARCHAR(50),
    status VARCHAR(255) DEFAULT '1'
);

CREATE TABLE tipos_acomodacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(80) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT '1',
    CONSTRAINT uq_tipos_acomodacao_nome UNIQUE (nome)
);

CREATE TABLE reservas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quarto_id INT NOT NULL,
    cliente_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    data_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10, 2) DEFAULT NULL,
    data_checkin DATE DEFAULT NULL,
    hora_checkin TIME DEFAULT NULL,
    data_checkout DATE DEFAULT NULL,
    hora_checkout TIME DEFAULT NULL,
    status ENUM('ativa', 'cancelada', 'finalizada') DEFAULT 'ativa',
    data_finalizacao DATETIME DEFAULT NULL,
    data_cancelamento DATETIME DEFAULT NULL,
    motivo_cancelamento TEXT,
    CONSTRAINT fk_reservas_quarto FOREIGN KEY (quarto_id) REFERENCES quartos(id),
    CONSTRAINT fk_reservas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE frigobar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    valor DECIMAL(10, 2) NOT NULL,
    quarto_id INT NOT NULL,
    status ENUM('1', '0') NOT NULL DEFAULT '1',
    status_frigobar ENUM('1', '0') NOT NULL DEFAULT '1',
    CONSTRAINT fk_frigobar_quarto FOREIGN KEY (quarto_id) REFERENCES quartos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE consumo_frigobar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reserva_id INT NOT NULL,
    frigobar_id INT NOT NULL,
    quantidade INT NOT NULL,
    valor_total DECIMAL(10, 2),
    CONSTRAINT fk_consumo_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id),
    CONSTRAINT fk_consumo_frigobar FOREIGN KEY (frigobar_id) REFERENCES frigobar(id)
);

CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    acao VARCHAR(20),
    mensagem TEXT NOT NULL
);

CREATE TABLE permissoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    perfil ENUM('adm', 'user') NOT NULL,
    pagina VARCHAR(80) NOT NULL,
    permitido TINYINT(1) NOT NULL DEFAULT '0',
    CONSTRAINT uq_perfil_pagina UNIQUE (perfil, pagina)
);
