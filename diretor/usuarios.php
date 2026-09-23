<?php
require_once 'sidebar.php';
require_once '../conexao.php';require_once '../includes/rbac.php';require_once '../includes/flash.php';require_once '../includes/audit.php';require_once '../includes/validation.php';require_once '../includes/deletion.php';
exigirCargo(['diretor','administrador']);
$perms=['agenda_ver'=>'Ver agenda geral','agenda_criar'=>'Criar consultas','agenda_cancelar'=>'Cancelar consultas','pacientes_ver'=>'Ver pacientes','profissionais_gerir'=>'Gerir profissionais','horarios_gerir'=>'Gerir horários','relatorios_ver'=>'Ver relatórios','usuarios_gerir'=>'Gerir usuários'];

if(isset($_POST['salvar_permissoes_id'])){
    $id=(int)($_POST['salvar_permissoes_id']??0);
    $sel=$_POST['permissoes']??[];
    try{
        $q=$pdo->prepare('SELECT id,cargo FROM usuarios WHERE id=?');$q->execute([$id]);$alvo=$q->fetch();
        if(!$alvo) throw new RuntimeException('Usuário não encontrado.');
        if($alvo['cargo']==='diretor') throw new RuntimeException('As permissões do diretor são totais e não precisam ser alteradas.');
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM usuario_permissoes WHERE usuario_id=?')->execute([$id]);
        $ins=$pdo->prepare('INSERT INTO usuario_permissoes(usuario_id,permissao) VALUES(?,?)');
        foreach($sel as $perm) if(isset($perms[$perm])) $ins->execute([$id,$perm]);
        $pdo->commit();
        auditar($pdo,'ATUALIZOU_PERMISSOES','usuario',$id,['permissoes'=>array_values(array_intersect(array_keys($perms),$sel))]);
        flash('success','Permissões atualizadas. Se o profissional estiver logado, ele deve sair e entrar novamente para receber as novas permissões.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error',$e instanceof RuntimeException?$e->getMessage():'Não foi possível atualizar as permissões.');}
    header('Location: usuarios.php#usuario-'.$id);exit;
}

if(isset($_POST['excluir_id'])){
    $id=(int)$_POST['excluir_id'];
    try { $msg=excluirUsuarioCompleto($pdo,$id); flash('success',$msg.' O mesmo e-mail/CPF poderá ser cadastrado novamente futuramente.'); }
    catch(Throwable $e){ flash('error',$e instanceof RuntimeException?$e->getMessage():'Não foi possível excluir o usuário.'); }
    header('Location: usuarios.php#lista-usuarios');exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $nome=trim($_POST['nome']??'');$email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL);$senha=$_POST['senha']??'';$conf=$_POST['confirmar']??'';$cargo=$_POST['cargo']??'';$pid=($_POST['profissional_id']??'')!==''?(int)$_POST['profissional_id']:null;
    $cpfRaw=trim($_POST['cpf']??'');$telefoneRaw=trim($_POST['telefone']??'');$cpf=somenteDigitos($cpfRaw);$telefone=somenteDigitos($telefoneRaw);
    $nascimento=$_POST['nascimento']??null;$endereco=trim($_POST['endereco']??'');$admissao=$_POST['data_admissao']??null;$sel=$_POST['permissoes']??[];$valid=['diretor','administrador','recepcionista','profissional'];
    if(mb_strlen($nome)<3||!$email||!in_array($cargo,$valid,true)) flash('error','Preencha nome, e-mail e cargo corretamente.');
    elseif(!cpfValido($cpf)) flash('error','CPF inválido. Informe exatamente 11 dígitos de um CPF válido.');
    elseif(!telefoneValido($telefone)) flash('error','Telefone inválido. Informe 10 dígitos para fixo ou 11 para celular.');
    elseif(!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',$senha)) flash('error','A senha precisa ter no mínimo 8 caracteres, 1 número e 1 caractere especial.');
    elseif($senha!==$conf) flash('error','As senhas não coincidem.');
    elseif($cargo==='profissional'&&!$pid) flash('error','Vincule o usuário profissional a um profissional cadastrado.');
    else try{
        $pdo->beginTransaction();
        $s=$pdo->prepare('INSERT INTO usuarios(nome,email,senha,cargo,profissional_id,cpf,telefone,nascimento,endereco,data_admissao,ativo) VALUES(?,?,?,?,?,?,?,?,?,?,1)');
        $s->execute([$nome,$email,password_hash($senha,PASSWORD_DEFAULT),$cargo,$pid,$cpf?:null,$telefone?:null,$nascimento?:null,$endereco,$admissao?:null]);
        $uid=(int)$pdo->lastInsertId();$ins=$pdo->prepare('INSERT INTO usuario_permissoes(usuario_id,permissao) VALUES(?,?)');
        foreach($sel as $p) if(isset($perms[$p])) $ins->execute([$uid,$p]);
        $pdo->commit();auditar($pdo,'CRIOU_FUNCIONARIO','usuario',$uid,['cargo'=>$cargo]);flash('success','Usuário criado com sucesso.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error','Não foi possível criar o usuário. Verifique se o e-mail já existe.');}
    header('Location: usuarios.php#novo-usuario');exit;
}
$usuarios=$pdo->query("SELECT u.id,u.nome,u.email,u.cargo,u.ativo,u.cpf,u.telefone,u.nascimento,u.endereco,u.data_admissao,u.criado_em,p.nome profissional,GROUP_CONCAT(up.permissao ORDER BY up.permissao SEPARATOR ',') permissoes FROM usuarios u LEFT JOIN profissionais p ON p.id=u.profissional_id LEFT JOIN usuario_permissoes up ON up.usuario_id=u.id GROUP BY u.id ORDER BY u.nome")->fetchAll();
$prof=$pdo->query('SELECT id,nome FROM profissionais WHERE ativo=1 ORDER BY nome')->fetchAll();
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuários | Aura</title><link rel="icon" type="image/png" sizes="512x512" href="../img/favicon-aura.png"><link rel="shortcut icon" href="../img/favicon.ico"><link rel="stylesheet" href="../css/style.css"></head><body class="director-page"><div class="app-shell director-shell"><?php renderDiretorSidebar('usuarios'); ?><main class="dashboard director-subpage"><div class="dash-head"><div><p class="eyebrow">CONTROLE DE ACESSO</p><h1>Usuários</h1><p class="muted">Cargos e permissões. A exclusão é definitiva e libera os dados para um novo cadastro no futuro.</p></div></div><?php mostrarFlash();?><details class="dash-card" open><summary><b>+ Adicionar usuário</b></summary><form method="post" class="form-grid" id="novo-usuario" data-preserve><label>Nome<input name="nome" required></label><label>E-mail<input type="email" name="email" required></label><label>Senha<input type="password" name="senha" minlength="8" autocomplete="new-password" required><small>Mínimo 8 caracteres, 1 número e 1 especial.</small></label><label>Confirmar senha<input type="password" name="confirmar" minlength="8" autocomplete="new-password" required></label><label>CPF<input name="cpf" inputmode="numeric" autocomplete="off" data-cpf maxlength="11" minlength="11" pattern="[0-9]{11}" placeholder="00000000000" required><small>Somente números: exatamente 11 dígitos e validação dos dígitos verificadores.</small></label><label>Telefone<input name="telefone" type="tel" inputmode="numeric" data-phone maxlength="15" placeholder="(11) 99999-9999" required><small>10 dígitos (fixo) ou 11 (celular).</small></label><label>Nascimento<input type="date" name="nascimento"></label><label>Endereço<input name="endereco"></label><label>Data de admissão<input type="date" name="data_admissao"></label><label>Cargo<select name="cargo" id="cargo" required><option value="">Selecione</option><option value="diretor">Diretor / Administrador total</option><option value="administrador">Administrador</option><option value="recepcionista">Recepcionista</option><option value="profissional">Profissional</option></select></label><label id="vinculoProf">Vincular profissional<select name="profissional_id"><option value="">Selecione</option><?php foreach($prof as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['nome'])?></option><?php endforeach;?></select></label><fieldset class="permissions"><legend>Permissões específicas</legend><?php foreach($perms as $k=>$v):?><label><input type="checkbox" name="permissoes[]" value="<?=$k?>"> <?=$v?></label><?php endforeach;?></fieldset><button class="btn">Criar usuário</button></form></details><div class="table-wrapper" id="lista-usuarios"><table><thead><tr><th>Nome</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Cargo</th><th>Vínculo</th><th>Permissões</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach($usuarios as $u): $atuais=$u['permissoes']?explode(',',$u['permissoes']):[]; ?><tr id="usuario-<?=$u['id']?>"><td><?=htmlspecialchars($u['nome'])?></td><td><?=htmlspecialchars($u['email'])?></td><td><?=htmlspecialchars($u['cpf']?formatarCpf($u['cpf']):'—')?></td><td><?=htmlspecialchars($u['telefone']?formatarTelefone($u['telefone']):'—')?></td><td><?=ucfirst($u['cargo'])?></td><td><?=htmlspecialchars($u['profissional']??'—')?></td><td class="permissions-cell"><?php if($u['cargo']==='diretor'): ?><span class="status confirmado">Acesso total</span><?php else: ?><details class="permission-editor"><summary>Editar permissões</summary><form method="post" class="permission-form" data-preserve><input type="hidden" name="salvar_permissoes_id" value="<?=$u['id']?>"><?php foreach($perms as $k=>$v):?><label><input type="checkbox" name="permissoes[]" value="<?=$k?>" <?=in_array($k,$atuais,true)?'checked':''?>> <?=$v?></label><?php endforeach;?><button class="btn btn-small" type="submit">Salvar permissões</button></form></details><?php endif;?></td><td><?=$u['ativo']?'Ativo':'Inativo'?></td><td><?php if($u['id']!=(int)$_SESSION['usuario_id'] && $u['cargo']!=='diretor'):?><form method="post" data-confirm="Excluir este usuário definitivamente? Os dados pessoais, permissões, tokens de redefinição e auditorias vinculadas serão removidos. Se for um profissional vinculado, o cadastro profissional, horários, disponibilidades e consultas desse profissional também serão apagados. Esta ação não pode ser desfeita." data-confirm-ok="Excluir definitivamente"><input type="hidden" name="excluir_id" value="<?=$u['id']?>"><button class="btn btn-danger btn-small" type="submit">Excluir</button></form><?php else:?>—<?php endif;?></td></tr><?php endforeach;?></tbody></table></div></main></div><script src="../js/app.js"></script></body></html>
