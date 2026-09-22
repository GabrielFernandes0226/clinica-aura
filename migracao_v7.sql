-- Espaço Aura V7 - preferências e controle de lembretes
ALTER TABLE agendamentos ADD COLUMN lembrete_minutos INT NOT NULL DEFAULT 60 AFTER paciente_id;
ALTER TABLE agendamentos ADD COLUMN lembrete_enviado_em DATETIME NULL AFTER lembrete_minutos;
ALTER TABLE agendamentos ADD COLUMN lembrete_status VARCHAR(30) NULL AFTER lembrete_enviado_em;
ALTER TABLE agendamentos ADD COLUMN lembrete_erro VARCHAR(255) NULL AFTER lembrete_status;
CREATE INDEX idx_agendamento_lembrete ON agendamentos(data, horario, lembrete_enviado_em);
