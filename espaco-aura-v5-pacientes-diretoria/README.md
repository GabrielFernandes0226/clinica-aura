# Espaço Aura — Sistema de Agendamento v3

## Instalação nova
1. Copie a pasta `clinica-agendamento` para `C:\xampp\htdocs\`.
2. Inicie Apache e MySQL no XAMPP.
3. No phpMyAdmin, importe `banco.sql`.
4. Acesse `http://localhost/clinica-agendamento/`.

## Atualizando a versão anterior sem perder dados
Faça backup do banco `clinica` e importe **somente** `migracao_v3.sql`. Não importe `banco.sql` sobre um banco que já possui dados.

## Primeiro acesso da Diretoria
- E-mail: `diretor@espacoaura.com.br`
- Senha temporária: `Aura@2026`

Troque a senha antes de uso real.

## Perfis
- Diretor: dashboard, relatórios, exportação CSV, usuários, cargos e permissões.
- Administrador: agenda, profissionais, horários e criação de outros administradores.
- Recepcionista: agenda geral e marcação presencial/telefone.
- Profissional: disponibilidade por data/horário e seus próximos pacientes.

## Observação
O projeto é adequado para ambiente local/de desenvolvimento. Antes de colocar dados reais de pacientes na internet, configure HTTPS, backups, política de privacidade/LGPD, controle de logs e endurecimento de segurança do servidor.

Contato da clínica: (11) 3683-3049

## V5
- Nova logo oficial em `img/logo-aura-oficial.png`.
- Área do paciente: `paciente/cadastro.php`, `paciente/login.php` e `paciente/index.php`.
- Diretoria: nova tela `diretor/funcionarios.php` com visão consolidada de funcionários, cargos, vínculos e permissões (senhas não são exibidas).
- Cards de serviços em carrossel responsivo.
- Ajustes de contraste/legibilidade de botões e alinhamentos.
- Para atualizar um banco V3/V4 existente, faça backup e execute `migracao_v5.sql`. Em instalação nova, use `banco.sql`.
