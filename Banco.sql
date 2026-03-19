create table usuarios(
    id int primary key auto_increment,
    login varchar(100) unique,
    senha varchar(32),
    perfil enum('adm','user')
);

CREATE TABLE quartos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quarto VARCHAR(50) NOT NULL,
    tipo VARCHAR(50),
    preco DECIMAL(10, 2),
    descricao TEXT
);

CREATE TABLE clientes (
  id int NOT NULL AUTO_INCREMENT,
  nome varchar(100) DEFAULT NULL,
  data_nascimento date DEFAULT NULL,
  cpf varchar(14) DEFAULT NULL,
  email varchar(100) DEFAULT NULL,
  telefone varchar(20) DEFAULT NULL,
  estado varchar(100) DEFAULT NULL,
  cidade varchar(50) DEFAULT NULL,
  status varchar(255) DEFAULT '1',
  PRIMARY KEY (id)
);
