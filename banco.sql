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
INSERT INTO profissionais(nome,especialidade) VALUES ('Dra. Ana Souza','Psicoterapia'),('Dra. Júlia Lima','Fonoterapia infantil'),('Dra. Marina Costa','Nutrição'),('Dr. Roberto Dias','Nutrição Bioimpedanciometria'),('Dr. Carlos Ferreira','Clínica geral');
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

USE clinica;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cpf VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS telefone VARCHAR(25) NULL, ADD COLUMN IF NOT EXISTS nascimento DATE NULL, ADD COLUMN IF NOT EXISTS endereco VARCHAR(255) NULL, ADD COLUMN IF NOT EXISTS data_admissao DATE NULL, ADD COLUMN IF NOT EXISTS excluido_em DATETIME NULL, ADD COLUMN IF NOT EXISTS excluido_por INT NULL;
CREATE TABLE IF NOT EXISTS auditoria (id BIGINT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NULL,usuario_nome VARCHAR(120) NULL,acao VARCHAR(100) NOT NULL,entidade VARCHAR(80) NOT NULL DEFAULT 'sistema',entidade_id INT NULL,detalhes TEXT NULL,ip VARCHAR(45) NULL,rota VARCHAR(255) NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_auditoria_data(criado_em),INDEX idx_auditoria_usuario(usuario_id));
CREATE TABLE IF NOT EXISTS redefinicoes_senha (id BIGINT AUTO_INCREMENT PRIMARY KEY,paciente_id INT NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expira_em DATETIME NOT NULL,usado_em DATETIME NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,INDEX idx_reset_paciente(paciente_id),INDEX idx_reset_expira(expira_em));

-- V7: lembretes configuráveis por consulta
ALTER TABLE agendamentos ADD COLUMN lembrete_minutos INT NOT NULL DEFAULT 60 AFTER paciente_id;
ALTER TABLE agendamentos ADD COLUMN lembrete_enviado_em DATETIME NULL AFTER lembrete_minutos;
ALTER TABLE agendamentos ADD COLUMN lembrete_status VARCHAR(30) NULL AFTER lembrete_enviado_em;
ALTER TABLE agendamentos ADD COLUMN lembrete_erro VARCHAR(255) NULL AFTER lembrete_status;
CREATE INDEX idx_agendamento_lembrete ON agendamentos(data, horario, lembrete_enviado_em);

CREATE TABLE IF NOT EXISTS redefinicoes_senha_usuarios (id BIGINT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expira_em DATETIME NOT NULL,usado_em DATETIME NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,INDEX idx_reset_usuario(usuario_id),INDEX idx_reset_usuario_expira(expira_em));
