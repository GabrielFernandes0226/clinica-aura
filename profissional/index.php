<?php
require_once '../conexao.php';require_once '../includes/rbac.php';require_once '../includes/flash.php';
exigirCargo(['profissional']);
$pid=(int)($_SESSION['profissional_id']??0);
if(!$pid){flash('error','Seu usuário ainda não está vinculado a um profissional.');header('Location: ../logout.php');exit;}
// Recarrega apenas as permissões deste profissional no servidor. Assim, a permissão
// "Gerir horários" é conferida no backend em cada acesso a esta área, sem alterar
// a lógica global de login/painel. Se houver uma falha temporária, mantém a sessão atual.
try {
  $uid=(int)($_SESSION['usuario_id']??0);
  if($uid>0) $_SESSION['permissoes']=carregarPermissoes($pdo,$uid);
} catch(Throwable $e) {}
$podeHorarios=pode('horarios_gerir');
$dias=['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];

if(isset($_POST['remover_disponibilidade'])){
  if(!$podeHorarios){flash('error','Seu acesso não permite gerenciar horários.');}
  else try{$id=(int)$_POST['remover_disponibilidade'];$s=$pdo->prepare('DELETE FROM disponibilidades WHERE id=? AND profissional_id=?');$s->execute([$id,$pid]);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Disponibilidade removida.':'Horário não encontrado.');}catch(Throwable $e){flash('error','Não foi possível remover a disponibilidade.');}
  header('Location:index.php#disponibilidades');exit;
}
if(isset($_POST['remover_recorrente'])){
  if(!$podeHorarios){flash('error','Seu acesso não permite gerenciar horários.');}
  else try{$id=(int)$_POST['remover_recorrente'];$s=$pdo->prepare('DELETE FROM horarios_profissionais WHERE id=? AND profissional_id=?');$s->execute([$id,$pid]);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Horário semanal removido.':'Horário não encontrado.');}catch(Throwable $e){flash('error','Não foi possível remover o horário semanal.');}
  header('Location:index.php#horarios');exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!$podeHorarios){flash('error','Seu acesso não permite gerenciar horários.');header('Location:index.php#horarios');exit;}
  $acao=$_POST['acao_horario']??'';
  if($acao==='adicionar_recorrente'){
    $dia=filter_var($_POST['dia_semana']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>6]]);$hora=$_POST['horario']??'';
    if($dia===false||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$hora)) flash('error','Informe dia da semana e horário válidos.');
    else try{$s=$pdo->prepare('INSERT IGNORE INTO horarios_profissionais(profissional_id,dia_semana,horario) VALUES(?,?,?)');$s->execute([$pid,$dia,$hora.':00']);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Horário semanal adicionado.':'Esse horário semanal já está cadastrado.');}catch(Throwable $e){flash('error','Não foi possível salvar o horário semanal.');}
    header('Location:index.php#horarios');exit;
  }
  if($acao==='adicionar_disponibilidade'){
    $data=$_POST['data']??'';$hora=$_POST['horario']??'';
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data)||$data<date('Y-m-d')||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$hora))flash('error','Informe data e horário válidos.');
    else try{$s=$pdo->prepare('INSERT IGNORE INTO disponibilidades(profissional_id,data,horario) VALUES(?,?,?)');$s->execute([$pid,$data,$hora.':00']);flash($s->rowCount()?'success':'warning',$s->rowCount()?'Disponibilidade adicionada.':'Esse horário já está cadastrado para a data.');}catch(Throwable $e){flash('error','Não foi possível salvar a disponibilidade.');}
    header('Location:index.php#disponibilidades');exit;
  }
}
$s=$pdo->prepare("SELECT a.*,TIMESTAMPDIFF(YEAR,a.nascimento,CURDATE()) idade FROM agendamentos a WHERE a.profissional_id=? AND a.data>=CURDATE() AND a.status<>'cancelado' ORDER BY a.data,a.horario LIMIT 100");$s->execute([$pid]);$ag=$s->fetchAll();
$s=$pdo->prepare('SELECT * FROM horarios_profissionais WHERE profissional_id=? AND ativo=1 ORDER BY dia_semana,horario');$s->execute([$pid]);$recorrentes=$s->fetchAll();
$s=$pdo->prepare('SELECT * FROM disponibilidades WHERE profissional_id=? AND data>=CURDATE() AND ativo=1 ORDER BY data,horario');$s->execute([$pid]);$disp=$s->fetchAll();
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Minha agenda | Aura</title><link rel="icon" type="image/png" sizes="512x512" href="../img/favicon-aura.png"><link rel="shortcut icon" href="../img/favicon.ico"><link rel="stylesheet" href="../css/style.css"></head><body><div class="app-shell"><aside class="sidebar"><a class="side-logo"><img src="../img/logo-aura-branca.png"></a><small>PROFISSIONAL</small><a class="active">Minha agenda</a><a href="#horarios">Meus horários</a><a href="../logout.php">Sair</a></aside><main class="dashboard"><div class="dash-head"><div><p class="eyebrow">ÁREA DO PROFISSIONAL</p><h1>Minha agenda</h1><p class="muted"><?=htmlspecialchars($_SESSION['usuario_nome'])?></p></div></div><?php mostrarFlash();?>
<section class="dash-card" id="horarios"><h2>Horários semanais</h2><?php if($podeHorarios):?><p class="muted">A permissão “Gerir horários” está ativa. Você pode adicionar e remover somente os seus próprios horários.</p><form method="post" class="filter-bar" id="novo-horario-profissional" data-preserve><input type="hidden" name="acao_horario" value="adicionar_recorrente"><label>Dia da semana<select name="dia_semana" required><?php foreach($dias as $i=>$d):?><option value="<?=$i?>"><?=$d?></option><?php endforeach;?></select></label><label>Horário<input type="time" name="horario" required></label><button class="btn">Adicionar horário</button></form><?php else:?><div class="inline-note">Você pode visualizar seus horários, mas não possui permissão para alterá-los.</div><?php endif;?><div class="availability-list"><?php foreach($recorrentes as $h):?><div class="availability-chip"><span><?=$dias[(int)$h['dia_semana']]?> · <?=substr($h['horario'],0,5)?></span><?php if($podeHorarios):?><form method="post" data-confirm="Remover este horário semanal?" data-confirm-ok="Remover"><input type="hidden" name="remover_recorrente" value="<?=$h['id']?>"><button type="submit" class="availability-remove" aria-label="Remover horário semanal">×</button></form><?php endif;?></div><?php endforeach;?><?php if(!$recorrentes):?><p class="muted">Nenhum horário semanal cadastrado.</p><?php endif;?></div></section>
<section class="dash-card" id="disponibilidades"><h2>Disponibilidades por data</h2><p class="muted">Use esta área quando quiser liberar um horário específico para uma data.</p><?php if($podeHorarios):?><form method="post" class="filter-bar" id="nova-disponibilidade-profissional" data-preserve><input type="hidden" name="acao_horario" value="adicionar_disponibilidade"><label>Data<input type="date" name="data" min="<?=date('Y-m-d')?>" required></label><label>Horário<input type="time" name="horario" required></label><button class="btn">Adicionar disponibilidade</button></form><?php endif;?><div class="availability-list"><?php foreach($disp as $d):?><div class="availability-chip"><span><?=date('d/m',strtotime($d['data']))?> · <?=substr($d['horario'],0,5)?></span><?php if($podeHorarios):?><form method="post" data-confirm="Remover este horário desta data?" data-confirm-ok="Remover"><input type="hidden" name="remover_disponibilidade" value="<?=$d['id']?>"><button type="submit" class="availability-remove" aria-label="Remover disponibilidade">×</button></form><?php endif;?></div><?php endforeach;?><?php if(!$disp):?><p class="muted">Nenhuma disponibilidade específica futura cadastrada.</p><?php endif;?></div></section>
<section class="dash-card"><h2>Próximos pacientes</h2><div class="table-wrapper flat"><table><thead><tr><th>Data</th><th>Hora</th><th>Paciente</th><th>Idade</th><th>Telefone</th><th>Serviço</th><th>Status</th></tr></thead><tbody><?php foreach($ag as $a):?><tr><td><?=date('d/m/Y',strtotime($a['data']))?></td><td><?=substr($a['horario'],0,5)?></td><td><?=htmlspecialchars($a['nome'])?></td><td><?=$a['idade']?> anos</td><td><?=htmlspecialchars($a['telefone'])?></td><td><?=htmlspecialchars($a['servico'])?></td><td><span class="status <?=$a['status']?>"><?=$a['status']?></span></td></tr><?php endforeach;?></tbody></table></div></section></main></div><script src="../js/app.js"></script></body></html>
