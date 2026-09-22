CREATE DATABASE IF NOT EXISTS clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica;
CREATE TABLE usuarios (id INT AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(100) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,senha VARCHAR(255) NOT NULL,cargo ENUM('diretor','administrador','recepcionista','profissional') NOT NULL DEFAULT 'administrador',profissional_id INT NULL,ativo TINYINT(1) NOT NULL DEFAULT 1,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE profissionais (id INT AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(100) NOT NULL,especialidade VARCHAR(100) NOT NULL,ativo TINYINT(1) NOT NULL DEFAULT 1);
ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_prof FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE SET NULL;
CREATE TABLE usuario_permissoes (usuario_id INT NOT NULL,permissao VARCHAR(80) NOT NULL,PRIMARY KEY(usuario_id,permissao),FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE);
CREATE TABLE horarios_profissionais (id INT AUTO_INCREMENT PRIMARY KEY,profissional_id INT NOT NULL,dia_semana TINYINT NOT NULL,horario TIME NOT NULL,ativo TINYINT(1) NOT NULL DEFAULT 1,FOREIGN KEY(profissional_id) REFERENCES profissionais(id) ON DELETE CASCADE,UNIQUE KEY unico_horario(profissional_id,dia_semana,horario));
CREATE TABLE disponibilidades (id INT AUTO_INCREMENT PRIMARY KEY,profissional_id INT NOT NULL,data DATE NOT NULL,horario TIME NOT NULL,ativo TINYINT(1) NOT NULL DEFAULT 1,FOREIGN KEY(profissional_id) REFERENCES profissionais(id) ON DELETE CASCADE,UNIQUE KEY unico_data_hora(profissional_id,data,horario));
CREATE TABLE agendamentos (id INT AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(100) NOT NULL,telefone VARCHAR(20) NOT NULL,nascimento DATE NOT NULL,servico VARCHAR(100) NOT NULL,profissional_id INT NOT NULL,data DATE NOT NULL,horario TIME NOT NULL,status VARCHAR(20) NOT NULL DEFAULT 'agendado',token VARCHAR(64) NOT NULL UNIQUE,criado_por INT NULL,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(profissional_id) REFERENCES profissionais(id),FOREIGN KEY(criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,INDEX idx_prof_data(profissional_id,data),INDEX idx_status(status));
INSERT INTO usuarios(nome,email,senha,cargo) VALUES('Diretor Aura','diretor@espacoaura.com.br','$2y$12$IjKXQ8FyVdwm9FzndVRRBul2dK.Bs0Mr4rUh3l3T7k3sWz6LPm7q6','diretor');
INSERT INTO profissionais(nome,especialidade) VALUES ('Dra. Ana Souza','Psicoterapia'),('Dra. Júlia Lima','Fonoterapia infantil'),('Dra. Marina Costa','Nutrição'),('Dr. Roberto Dias','Exame impedanciometria'),('Dr. Carlos Ferreira','Clínica geral');
CREATE TABLE pacientes (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 telefone VARCHAR(20) NOT NULL,
 nascimento DATE NOT NULL,
 senha VARCHAR(255) NOT NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE agendamentos ADD COLUMN paciente_id INT NULL;
ALTER TABLE agendamentos ADD CONSTRAINT fk_agendamento_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL;
CREATE INDEX idx_agendamento_paciente ON agendamentos(paciente_id);
