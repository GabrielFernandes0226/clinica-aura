USE clinica;

-- Atualiza o nome do serviço/especialidade sem apagar histórico.
UPDATE profissionais
SET especialidade = 'Nutrição – Bioimpedanciometria'
WHERE especialidade IN ('Exame impedanciometria','Impedanciometria','Nutrição Bioimpedanciometria','Nutrição — Bioimpedanciometria');

UPDATE agendamentos
SET servico = 'Nutrição – Bioimpedanciometria'
WHERE servico IN ('Exame impedanciometria','Impedanciometria','Nutrição Bioimpedanciometria','Nutrição — Bioimpedanciometria');
