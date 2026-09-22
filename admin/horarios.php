<?php
require_once '../diretor/sidebar.php';
require_once '../includes/rbac.php';require_once '../conexao.php';require_once '../includes/flash.php';
exigirCargo(['administrador','diretor']);
$dias=['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
try{$prof=$pdo->query('SELECT * FROM profissionais WHERE ativo=1 ORDER BY nome')->fetchAll();}catch(Throwable $e){$prof=[];flash('error','Não foi possível carregar os profissionais.');}

if(isset($_POST['remover_id'])){
  $id=(int)$_POST['remover_id'];
  try{$s=$pdo->prepare('DELETE FROM horarios_profissionais WHERE id=?');$s->execute([$id]);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Horário removido.':'Horário não encontrado.');}
  catch(Throwable $e){flash('error','Não foi possível remover o horário.');}
  header('Location: horarios.php#lista-horarios');exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $pid=(int)($_POST['profissional_id']??0);$dia=filter_var($_POST['dia_semana']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>6]]);$hora=$_POST['horario']??'';
  if(!$pid||$dia===false||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$hora)) flash('error','Selecione profissional, dia e horário válidos.');
  else try{$s=$pdo->prepare('INSERT IGNORE INTO horarios_profissionais(profissional_id,dia_semana,horario) VALUES(?,?,?)');$s->execute([$pid,$dia,$hora.':00']);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Horário adicionado com sucesso.':'Esse horário já estava cadastrado.');}catch(Throwable $e){flash('error','Não foi possível adicionar o horário.');}
  header('Location: horarios.php#novo-horario');exit;
}
try{$hor=$pdo->query('SELECT h.*,p.nome profissional FROM horarios_profissionais h JOIN profissionais p ON p.id=h.profissional_id ORDER BY p.nome,h.dia_semana,h.horario')->fetchAll();}catch(Throwable $e){$hor=[];flash('error','Não foi possível carregar os horários.');}

function conteudoHorarios(array $prof,array $hor,array $dias):void{ ?>
<?php mostrarFlash();?><form method="post" class="form-card admin-form" id="novo-horario" data-preserve><label>Profissional</label><select name="profissional_id" required><option value="">Selecione</option><?php foreach($prof as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['nome'])?></option><?php endforeach;?></select><label>Dia da semana</label><select name="dia_semana" required><?php foreach($dias as $i=>$d):?><option value="<?=$i?>"><?=$d?></option><?php endforeach;?></select><label>Horário</label><input type="time" name="horario" required><button class="btn" type="submit">Adicionar horário</button></form>
<div class="table-wrapper" id="lista-horarios"><table><thead><tr><th>Profissional</th><th>Dia</th><th>Horário</th><th>Ações</th></tr></thead><tbody><?php foreach($hor as $h):?><tr><td><?=htmlspecialchars($h['profissional'])?></td><td><?=$dias[(int)$h['dia_semana']]?></td><td><?=substr($h['horario'],0,5)?></td><td><form method="post" data-confirm="Remover este horário?" data-confirm-ok="Remover"><input type="hidden" name="remover_id" value="<?=$h['id']?>"><button class="btn btn-danger btn-small">Remover</button></form></td></tr><?php endforeach;?></tbody></table></div>
<?php }
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Horários | Espaço Aura</title><link rel="icon" type="image/png" sizes="512x512" href="../img/favicon-aura.png"><link rel="shortcut icon" href="../img/favicon.ico"><link rel="stylesheet" href="../css/style.css"></head><body class="<?=isDiretor()?'director-page':''?>"><?php if(isDiretor()): ?><div class="app-shell director-shell"><?php renderDiretorSidebar('horarios','admin'); ?><main class="dashboard director-subpage"><div class="dash-head"><div><p class="eyebrow">DIRETORIA</p><h1>Horários</h1></div></div><div class="director-content-wrap"><?php conteudoHorarios($prof,$hor,$dias); ?></div></main></div><?php else: ?><header class="header"><div class="container nav"><a href="index.php" class="brand"><img src="../img/logo-aura-oficial.png" alt="Espaço Aura"></a><a class="back-link" href="index.php">← Painel</a></div></header><main class="panel-page"><div class="container"><h1 class="admin-title">Horários</h1><?php conteudoHorarios($prof,$hor,$dias); ?></div></main><?php endif; ?><script src="../js/app.js"></script></body></html>
