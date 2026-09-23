<?php
require_once '../diretor/sidebar.php';
require_once '../includes/rbac.php';
require_once '../conexao.php';
require_once '../includes/flash.php';
require_once '../includes/deletion.php';
exigirCargo(['administrador','diretor']);

if (isset($_POST['excluir_id'])) {
    $id=(int)$_POST['excluir_id'];
    try {
        excluirProfissionalCompleto($pdo,$id);
        flash('success','Profissional, acesso vinculado, horários e dados relacionados foram removidos definitivamente. Ele poderá ser cadastrado novamente no futuro.');
    } catch (Throwable $e) {
        flash('error',$e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível remover o profissional.');
    }
    header('Location: profissionais.php#lista-profissionais'); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nome=trim($_POST['nome']??'');
    $esp=trim($_POST['especialidade']??'');
    $valid=['Psicoterapia','Fonoterapia infantil','Nutrição','Nutrição – Bioimpedanciometria','Clínica geral'];
    if(mb_strlen($nome)<3 || !in_array($esp,$valid,true)) {
        flash('error','Preencha nome e especialidade corretamente.');
    } else {
        try {
            $s=$pdo->prepare('INSERT INTO profissionais(nome,especialidade) VALUES(?,?)');
            $s->execute([$nome,$esp]);
            flash('success','Profissional adicionado com sucesso.');
        } catch(Throwable $e) { flash('error','Não foi possível adicionar o profissional.'); }
    }
    header('Location: profissionais.php#novo-profissional'); exit;
}

try {$prof=$pdo->query('SELECT * FROM profissionais ORDER BY nome')->fetchAll();}
catch(Throwable $e){$prof=[];flash('error','Não foi possível carregar os profissionais.');}

function conteudoProfissionais(array $prof): void { ?>
    <?php mostrarFlash(); ?>
    <form method="post" class="form-card admin-form" id="novo-profissional" data-preserve>
      <label>Nome</label><input name="nome" maxlength="100" required>
      <label>Especialidade</label>
      <select name="especialidade" required>
        <option value="">Selecione</option>
        <option>Psicoterapia</option><option>Fonoterapia infantil</option><option>Nutrição</option>
        <option>Nutrição – Bioimpedanciometria</option><option>Clínica geral</option>
      </select>
      <button class="btn" type="submit">Adicionar profissional</button>
    </form>
    <div class="table-wrapper" id="lista-profissionais"><table><thead><tr><th>Nome</th><th>Especialidade</th><th>Status</th><th>Ações</th></tr></thead><tbody>
    <?php foreach($prof as $p): ?>
      <tr>
        <td><?=htmlspecialchars($p['nome'])?></td><td><?=htmlspecialchars($p['especialidade'])?></td><td><?=$p['ativo']?'Ativo':'Inativo'?></td>
        <td><form method="post" data-confirm="Remover este profissional definitivamente? Consultas, horários, disponibilidades e eventual acesso vinculado também serão apagados. Esta ação não pode ser desfeita." data-confirm-ok="Remover definitivamente"><input type="hidden" name="excluir_id" value="<?=$p['id']?>"><button class="btn btn-danger btn-small" type="submit">Remover profissional</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php }
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profissionais | Espaço Aura</title><link rel="icon" type="image/png" sizes="512x512" href="../img/favicon-aura.png"><link rel="shortcut icon" href="../img/favicon.ico"><link rel="stylesheet" href="../css/style.css"></head><body class="<?=isDiretor()?'director-page':''?>">
<?php if(isDiretor()): ?>
<div class="app-shell director-shell"><?php renderDiretorSidebar('profissionais','admin'); ?><main class="dashboard director-subpage"><div class="dash-head"><div><p class="eyebrow">DIRETORIA</p><h1>Profissionais</h1><p class="muted">Cadastre profissionais ou exclua definitivamente o cadastro e todos os dados vinculados. Para permitir que um profissional altere horários, use Diretoria → Usuários e acessos → Editar permissões → Gerir horários.</p></div></div><div class="director-content-wrap"><?php conteudoProfissionais($prof); ?></div></main></div>
<?php else: ?>
<header class="header"><div class="container nav"><a href="index.php" class="brand"><img src="../img/logo-aura-oficial.png" alt="Espaço Aura"></a><a class="back-link" href="index.php">← Painel</a></div></header><main class="panel-page"><div class="container"><h1 class="admin-title">Profissionais</h1><?php conteudoProfissionais($prof); ?></div></main>
<?php endif; ?><script src="../js/app.js"></script></body></html>
