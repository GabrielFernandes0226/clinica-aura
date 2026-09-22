USE clinica;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cargo ENUM('diretor','administrador','recepcionista','profissional') NOT NULL DEFAULT 'administrador';
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS profissional_id INT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ativo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS nascimento DATE NULL AFTER telefone;
ALTER TABLE agendamentos MODIFY servico VARCHAR(100) NOT NULL;
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS criado_por INT NULL;
CREATE TABLE IF NOT EXISTS usuario_permissoes (usuario_id INT NOT NULL,permissao VARCHAR(80) NOT NULL,PRIMARY KEY(usuario_id,permissao));
CREATE TABLE IF NOT EXISTS disponibilidades (id INT AUTO_INCREMENT PRIMARY KEY,profissional_id INT NOT NULL,data DATE NOT NULL,horario TIME NOT NULL,ativo TINYINT(1) NOT NULL DEFAULT 1,UNIQUE KEY unico_data_hora(profissional_id,data,horario));
-- Após migrar, use a tela da Diretoria para criar novos usuários com a política de senha forte.
INSERT INTO usuarios(nome,email,senha,cargo,ativo)
SELECT 'Diretor Aura','diretor@espacoaura.com.br','$2y$12$VSrwfi9mO9knIsDFslir1uwiZPeujayvXpX/7/xOgyJrtAGwUnYva','diretor',1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email='diretor@espacoaura.com.br');
