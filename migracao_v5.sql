USE clinica;
CREATE TABLE IF NOT EXISTS pacientes (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 telefone VARCHAR(20) NOT NULL,
 nascimento DATE NOT NULL,
 senha VARCHAR(255) NOT NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS paciente_id INT NULL;
-- Em bancos que já possuem a FK/índice, as duas instruções abaixo podem ser ignoradas se o MySQL informar nome duplicado.
ALTER TABLE agendamentos ADD CONSTRAINT fk_agendamento_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL;
CREATE INDEX idx_agendamento_paciente ON agendamentos(paciente_id);
