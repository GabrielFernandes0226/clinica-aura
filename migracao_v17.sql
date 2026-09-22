USE clinica;

-- V17: padroniza o nome solicitado do serviço sem apagar histórico.
UPDATE profissionais
SET especialidade = 'Nutrição Bioimpedanciometria'
WHERE especialidade IN ('Exame impedanciometria','Impedanciometria','Nutrição — Bioimpedanciometria');

UPDATE agendamentos
SET servico = 'Nutrição Bioimpedanciometria'
WHERE servico IN ('Exame impedanciometria','Impedanciometria','Nutrição — Bioimpedanciometria');
