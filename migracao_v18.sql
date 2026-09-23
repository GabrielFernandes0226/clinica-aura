USE clinica;

-- V18: padroniza o nome solicitado sem apagar histórico.
UPDATE profissionais
SET especialidade = 'Nutrição – Bioimpedanciometria'
WHERE especialidade IN (
  'Exame impedanciometria',
  'Impedanciometria',
  'Nutrição Bioimpedanciometria',
  'Nutrição — Bioimpedanciometria'
);

UPDATE agendamentos
SET servico = 'Nutrição – Bioimpedanciometria'
WHERE servico IN (
  'Exame impedanciometria',
  'Impedanciometria',
  'Nutrição Bioimpedanciometria',
  'Nutrição — Bioimpedanciometria'
);

-- A V18 faz as exclusões de forma explícita dentro de transação,
-- então funciona também em bancos antigos cujas FKs ainda usam SET NULL.
-- Em instalações novas, banco.sql já cria os vínculos apropriados com ON DELETE CASCADE.
CREATE TABLE IF NOT EXISTS redefinicoes_senha_usuarios (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_reset_usuario(usuario_id),
  INDEX idx_reset_usuario_expira(expira_em)
);
