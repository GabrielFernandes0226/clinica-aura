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


## V6
- Execute `migracao_v6.sql` se estiver atualizando a V5.
- O botão Agendar exige autenticação do paciente.
- Recuperação de senha usa `mail()` do PHP. No XAMPP, configure SMTP/sendmail para entrega real. Em modo AURA_DEV o link também é registrado em `storage/reset-mails.log` para teste local.
- Exclusão de funcionário é lógica (desativa acesso), preservando histórico e auditoria.

## V7 — responsividade e lembretes
- Interface revisada para celulares pequenos, smartphones, tablets, notebooks e desktops.
- No agendamento, o paciente escolhe se deseja lembrete e com quanta antecedência (minutos/horas, inclusive personalizado até 7 dias).
- A rotina `services/lembretes.php` processa os lembretes por e-mail e evita reenvio usando `lembrete_enviado_em`.
- Em desenvolvimento (`AURA_DEV=true`), os lembretes são gravados em `storage/lembretes-dev.log`, permitindo testar sem SMTP.
- Em produção, configure o envio de e-mail do PHP/SMTP, altere `AURA_DEV` para `false` e programe `php C:\\xampp\\htdocs\\clinica-agendamento\\services\\lembretes.php` para executar a cada minuto no Agendador de Tarefas do Windows.
- Quem já usa a V6 deve fazer backup e executar somente `migracao_v7.sql`.

## V8 — senha, recuperação e interações
- Todos os campos de senha recebem botão de visualizar/ocultar automaticamente.
- Cadastros de usuários internos e administradores exigem confirmação da senha.
- Pacientes continuam com recuperação por e-mail e a equipe agora também possui “Esqueceu sua senha?”.
- Mensagens e confirmações foram padronizadas em pop-ups centrais, substituindo diálogos nativos do navegador.
- Execute `migracao_v8.sql` em instalações existentes (as telas de recuperação também criam a tabela quando o usuário do banco possui permissão).
- O envio usa `mail()` do PHP. Em `AURA_DEV`, o link de teste fica em `storage/reset-mails.log`.

## V15 — exclusão definitiva, horários e validações
Para bancos já existentes, execute também `migracao_v15.sql`. A aplicação agora faz exclusão definitiva de usuários/profissionais pelas telas autorizadas, permite que profissionais com a permissão `horarios_gerir` adicionem/removam suas disponibilidades, valida CPF pelos dígitos verificadores e limita telefones a 10/11 dígitos. O serviço “Exame impedanciometria” foi renomeado para “Nutrição – Bioimpedanciometria”.


## V16 — correção de entrega
Esta versão torna as alterações da V15 visíveis e operacionais nas telas usadas pela Diretoria. Em Usuários e acessos, usuários existentes agora possuem **Editar permissões**, permitindo ativar **Gerir horários** sem recriar a conta. A área Profissionais exibe **Excluir profissional** explicitamente. CPF e telefone são validados no navegador e no servidor. Em caso de erro, o sistema restaura campos não sensíveis e retorna ao bloco/formulário em que a ação ocorreu.


## V17 — ajustes solicitados em profissionais e formulários
- A aba Profissionais mantém a ação de exclusão definitiva com remoção de acesso, horários, disponibilidades, consultas e auditorias ligadas ao cadastro removido.
- Profissionais com a permissão `horarios_gerir` podem criar/remover os próprios horários semanais e disponibilidades específicas por data.
- Em erros, o sistema restaura o formulário correto, seus valores não sensíveis e a posição em que o usuário estava.
- CPF continua limitado a 11 dígitos reais e validado pelos dígitos verificadores; telefones aceitam somente 10 dígitos (fixo) ou 11 (celular).
- O serviço foi padronizado para **Nutrição – Bioimpedanciometria**. Em banco existente, execute `migracao_v17.sql`.

## V18 — exclusão completa, validações e acesso mobile
- A aba **Profissionais** possui confirmação antes de remover um profissional.
- A permissão `horarios_gerir` é validada novamente no backend a cada requisição autenticada; profissionais autorizados podem criar e remover somente os próprios horários.
- Exclusões de usuários/profissionais usam transação e removem consultas/lembretes vinculados, horários, disponibilidades, permissões, redefinições de senha e auditorias relacionadas.
- CPF é digitado somente com números, limitado a 11 dígitos e validado pelos dígitos verificadores; telefones continuam aceitando 10 dígitos (fixo) ou 11 (celular), com máscara apenas visual.
- O serviço foi padronizado para **Nutrição – Bioimpedanciometria**.
- No celular, os painéis internos agora exibem um botão **Menu** que abre todas as opções disponíveis ao perfil, sem esconder atalhos em rolagem horizontal.
- POSTs são enviados de forma assíncrona no navegador: erros e avisos aparecem em popup sem recarregar a tela nem apagar os campos; sucessos atualizam a tela normalmente.
- Para banco já existente, execute `migracao_v18.sql` após atualizar os arquivos.
