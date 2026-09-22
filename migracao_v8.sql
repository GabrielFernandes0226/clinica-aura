-- V8 - recuperação de senha para usuários da equipe
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
