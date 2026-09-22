<?php
require_once '../conexao.php';
require_once '../includes/rbac.php';
require_once '../includes/flash.php';
require_once 'sidebar.php';
exigirCargo(['diretor']);

$inicioMes=date('Y-m-01');
$fimMes=date('Y-m-t');
$mesNome=['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
$nomeMes=$mesNome[date('m')].' de '.date('Y');

$stats=['total'=>0,'confirmadas'=>0,'pacientes'=>0,'canceladas'=>0];
$serv=[];$profs=[];$consultasMes=[];$statusMes=[];$dailyMap=[];$monthMap=[];
try {
    $q=$pdo->prepare("SELECT COUNT(*) total, SUM(status='confirmado') confirmadas, COUNT(DISTINCT telefone) pacientes, SUM(status='cancelado') canceladas FROM agendamentos WHERE data BETWEEN ? AND ?");
    $q->execute([$inicioMes,$fimMes]);
    $stats=$q->fetch() ?: $stats;

    $q=$pdo->prepare("SELECT servico,COUNT(*) total FROM agendamentos WHERE data BETWEEN ? AND ? AND status<>'cancelado' GROUP BY servico ORDER BY total DESC,servico ASC");
    $q->execute([$inicioMes,$fimMes]);$serv=$q->fetchAll();

    $q=$pdo->prepare("SELECT p.nome,p.especialidade,COUNT(a.id) total FROM profissionais p LEFT JOIN agendamentos a ON a.profissional_id=p.id AND a.data BETWEEN ? AND ? AND a.status<>'cancelado' WHERE p.ativo=1 GROUP BY p.id,p.nome,p.especialidade ORDER BY total DESC,p.nome ASC");
    $q->execute([$inicioMes,$fimMes]);$profs=$q->fetchAll();

    $q=$pdo->prepare("SELECT a.id,a.data,a.horario,a.nome paciente,a.servico,a.status,p.nome profissional,p.especialidade FROM agendamentos a JOIN profissionais p ON p.id=a.profissional_id WHERE a.data BETWEEN ? AND ? ORDER BY a.data DESC,a.horario DESC LIMIT 100");
    $q->execute([$inicioMes,$fimMes]);$consultasMes=$q->fetchAll();

    $q=$pdo->prepare("SELECT status,COUNT(*) total FROM agendamentos WHERE data BETWEEN ? AND ? GROUP BY status ORDER BY total DESC");
    $q->execute([$inicioMes,$fimMes]);$statusMes=$q->fetchAll();

    $q=$pdo->prepare("SELECT DAY(data) dia,COUNT(*) total FROM agendamentos WHERE data BETWEEN ? AND ? AND status<>'cancelado' GROUP BY DAY(data)");
    $q->execute([$inicioMes,$fimMes]);foreach($q->fetchAll() as $r)$dailyMap[(int)$r['dia']]=(int)$r['total'];

    $firstSix=date('Y-m-01',strtotime('-5 months'));
    $q=$pdo->prepare("SELECT DATE_FORMAT(data,'%Y-%m') mes,COUNT(*) total FROM agendamentos WHERE data>=? AND data<=? AND status<>'cancelado' GROUP BY DATE_FORMAT(data,'%Y-%m')");
    $q->execute([$firstSix,$fimMes]);foreach($q->fetchAll() as $r)$monthMap[$r['mes']]=(int)$r['total'];
} catch(Throwable $e) {
    flash('error','Não foi possível carregar todos os indicadores. O painel continuará exibindo os blocos disponíveis.');
}

$monthLabels=[];$monthValues=[];
for($i=5;$i>=0;$i--){$ts=strtotime("-$i months");$key=date('Y-m',$ts);$monthLabels[]=date('m/Y',$ts);$monthValues[]=$monthMap[$key]??0;}
$dayLabels=[];$dayValues=[];$days=(int)date('t');
for($d=1;$d<=$days;$d++){if($d===1||$d===$days||$d%5===0||isset($dailyMap[$d])){$dayLabels[]=(string)$d;$dayValues[]=$dailyMap[$d]??0;}}

$totalMes=(int)($stats['total']??0);$confirmadas=(int)($stats['confirmadas']??0);$canceladas=(int)($stats['canceladas']??0);$pacientes=(int)($stats['pacientes']??0);
$topService=$serv[0]??null;$bottomService=$serv?end($serv):null;reset($serv);$topProf=$profs[0]??null;
$maxServ=max(array_map(fn($x)=>(int)$x['total'],$serv)?:[1]);$maxProf=max(array_map(fn($x)=>(int)$x['total'],$profs)?:[1]);
$statusKnown=['confirmado'=>0,'cancelado'=>0,'agendado'=>0,'outros'=>0];foreach($statusMes as $s){$k=strtolower((string)$s['status']);if(isset($statusKnown[$k]))$statusKnown[$k]+=(int)$s['total'];else$statusKnown['outros']+=(int)$s['total'];}
$den=max(1,array_sum($statusKnown));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Diretoria | Espaço Aura</title><link rel="icon" type="image/png" sizes="512x512" href="../img/favicon-aura.png"><link rel="shortcut icon" href="../img/favicon.ico"><link rel="stylesheet" href="../css/style.css"></head>
<body class="director-page"><div class="app-shell director-shell"><?php renderDiretorSidebar('dashboard'); ?><main class="dashboard director-dashboard">
<header class="dash-head director-head"><div><p class="eyebrow">CENTRAL EXECUTIVA · <?=htmlspecialchars($nomeMes)?></p><h1>Dashboard da diretoria</h1><p class="muted">Acompanhe consultas, profissionais, demanda dos serviços e pontos de atenção do mês.</p></div><div class="user-chip"><span class="online-dot"></span><?=htmlspecialchars($_SESSION['usuario_nome']??'Diretoria')?><small>Diretor · sessão ativa</small></div></header>
<?php mostrarFlash(); ?>

<section class="director-stat-grid stagger-group" aria-label="Indicadores do mês">
<article class="director-stat reveal-up"><div class="stat-icon">▦</div><div><span>Consultas no mês</span><strong class="count-up" data-count="<?=$totalMes?>"><?=$totalMes?></strong><small><?=htmlspecialchars($nomeMes)?></small></div></article>
<article class="director-stat reveal-up"><div class="stat-icon">✓</div><div><span>Confirmadas</span><strong class="count-up" data-count="<?=$confirmadas?>"><?=$confirmadas?></strong><small><?= $totalMes ? round($confirmadas/$totalMes*100) : 0 ?>% das consultas</small></div></article>
<article class="director-stat reveal-up"><div class="stat-icon">◎</div><div><span>Pacientes no mês</span><strong class="count-up" data-count="<?=$pacientes?>"><?=$pacientes?></strong><small>Telefones únicos registrados</small></div></article>
<article class="director-stat reveal-up"><div class="stat-icon">♧</div><div><span>Profissionais com agenda</span><strong class="count-up" data-count="<?=count(array_filter($profs,fn($p)=>(int)$p['total']>0))?>"><?=count(array_filter($profs,fn($p)=>(int)$p['total']>0))?></strong><small><?=count($profs)?> profissionais ativos</small></div></article>
</section>

<section class="director-grid director-grid-primary">
<article class="director-card director-card-wide reveal-up"><div class="director-card-head"><div><p class="eyebrow">MOVIMENTO</p><h2>Consultas ao longo do mês</h2><p class="muted mini-copy">Quantidade por dia em <?=htmlspecialchars($nomeMes)?>, sem contar cancelamentos.</p></div><span class="chart-hint">Interaja com os pontos</span></div><div class="director-line-chart" data-labels='<?=htmlspecialchars(json_encode($dayLabels,JSON_UNESCAPED_UNICODE),ENT_QUOTES)?>' data-values='<?=htmlspecialchars(json_encode($dayValues),ENT_QUOTES)?>'><svg viewBox="0 0 700 250" role="img" aria-label="Consultas por dia no mês atual"></svg><div class="chart-tooltip" aria-hidden="true"></div></div></article>
<article class="director-card reveal-up"><div class="director-card-head"><div><p class="eyebrow">STATUS</p><h2>Situação das consultas</h2><p class="muted mini-copy">Distribuição no mês atual.</p></div></div><div class="status-summary"><div class="donut-chart" style="--confirmed:<?=round($statusKnown['confirmado']/$den*100,2)?>;--cancelled:<?=round($statusKnown['cancelado']/$den*100,2)?>" aria-label="Distribuição dos status"><div><strong class="count-up" data-count="<?=$totalMes?>"><?=$totalMes?></strong><span>Total</span></div></div><div class="status-legend"><span><i class="legend-dot confirmed"></i>Confirmadas <b><?=$statusKnown['confirmado']?></b></span><span><i class="legend-dot scheduled"></i>Agendadas <b><?=$statusKnown['agendado']?></b></span><span><i class="legend-dot cancelled"></i>Canceladas <b><?=$statusKnown['cancelado']?></b></span><span><i class="legend-dot other"></i>Outros <b><?=$statusKnown['outros']?></b></span></div></div></article>
</section>

<section class="director-grid director-grid-even">
<article class="director-card reveal-left"><div class="director-card-head"><div><p class="eyebrow">EQUIPE</p><h2>Quem mais atendeu no mês</h2><p class="muted mini-copy">Quantidade de consultas não canceladas por profissional.</p></div></div><div class="interactive-bars"><?php foreach($profs as $x):$pct=$maxProf?round((int)$x['total']/$maxProf*100):0;?><button type="button" class="metric-bar" style="--target:<?=$pct?>%" data-value="<?=intval($x['total'])?>"><span><b><?=htmlspecialchars($x['nome'])?></b><em><?=intval($x['total'])?> consulta<?=intval($x['total'])===1?'':'s'?></em></span><i><u></u></i><small><?=htmlspecialchars($x['especialidade'])?></small></button><?php endforeach;?><?php if(!$profs):?><p class="empty-inline">Cadastre profissionais para acompanhar os atendimentos aqui.</p><?php endif;?></div></article>
<article class="director-card reveal-right"><div class="director-card-head"><div><p class="eyebrow">DEMANDA</p><h2>Consultas que mais e menos saem</h2><p class="muted mini-copy">Ranking dos serviços realizados/agendados no mês, sem cancelamentos.</p></div></div><div class="interactive-bars service-demand-bars"><?php foreach($serv as $i=>$x):$pct=$maxServ?round((int)$x['total']/$maxServ*100):0;?><button type="button" class="metric-bar <?=$i===0?'top-demand':''?> <?=$i===count($serv)-1?'low-demand':''?>" style="--target:<?=$pct?>%"><span><b><?=htmlspecialchars($x['servico'])?></b><em><?=intval($x['total'])?> consulta<?=intval($x['total'])===1?'':'s'?></em></span><i><u></u></i></button><?php endforeach;?><?php if(!$serv):?><p class="empty-inline">Ainda não há consultas neste mês. Os rankings aparecerão automaticamente assim que houver registros.</p><?php endif;?></div></article>
</section>

<section class="director-card insight-card reveal-up"><div class="director-card-head"><div><p class="eyebrow">INSIGHTS</p><h2>Sugestões baseadas nos dados do mês</h2><p class="muted mini-copy">Leituras automáticas para ajudar na organização da clínica.</p></div></div><div class="insight-grid">
<div class="insight-item"><span class="insight-icon">↗</span><div><b>Serviço com maior procura</b><p><?php if($topService):?><?=htmlspecialchars($topService['servico'])?> lidera com <?=intval($topService['total'])?> consulta<?=intval($topService['total'])===1?'':'s'?>. Considere conferir se há horários suficientes para absorver essa demanda.<?php else:?>Ainda não existem dados suficientes neste mês para identificar o serviço mais procurado.<?php endif;?></p></div></div>
<div class="insight-item"><span class="insight-icon">◎</span><div><b>Serviço com menor procura</b><p><?php if($bottomService && $topService && $bottomService['servico']!==$topService['servico']):?><?=htmlspecialchars($bottomService['servico'])?> teve <?=intval($bottomService['total'])?> consulta<?=intval($bottomService['total'])===1?'':'s'?>. Pode valer revisar divulgação, horários disponíveis e procura desse atendimento.<?php else:?>Quando houver mais de um serviço com consultas, o sistema destacará aqui o de menor procura.<?php endif;?></p></div></div>
<div class="insight-item"><span class="insight-icon">♧</span><div><b>Distribuição da equipe</b><p><?php if($topProf && (int)$topProf['total']>0):?><?=htmlspecialchars($topProf['nome'])?> concentrou o maior volume no mês, com <?=intval($topProf['total'])?> atendimento<?=intval($topProf['total'])===1?'':'s'?>. Acompanhe a carga da agenda para manter uma boa distribuição.<?php else:?>Ainda não há atendimentos suficientes para comparar a distribuição entre profissionais.<?php endif;?></p></div></div>
</div></section>

<section class="director-card reveal-up"><div class="director-card-head"><div><p class="eyebrow">DETALHAMENTO</p><h2>Quem atendeu cada consulta</h2><p class="muted mini-copy">Consultas registradas em <?=htmlspecialchars($nomeMes)?>.</p></div><a class="btn btn-small" href="relatorios.php?de=<?=$inicioMes?>&ate=<?=$fimMes?>">Ver relatório completo</a></div><div class="table-wrapper director-table"><table><thead><tr><th>Data</th><th>Hora</th><th>Paciente</th><th>Serviço</th><th>Profissional</th><th>Especialidade</th><th>Status</th></tr></thead><tbody><?php if($consultasMes):foreach($consultasMes as $r):?><tr><td><?=date('d/m/Y',strtotime($r['data']))?></td><td><?=substr($r['horario'],0,5)?></td><td><?=htmlspecialchars($r['paciente'])?></td><td><?=htmlspecialchars($r['servico'])?></td><td><strong><?=htmlspecialchars($r['profissional'])?></strong></td><td><?=htmlspecialchars($r['especialidade'])?></td><td><span class="status <?=htmlspecialchars(strtolower($r['status']))?>"><?=htmlspecialchars(ucfirst($r['status']))?></span></td></tr><?php endforeach;else:?><tr><td colspan="7"><div class="table-empty-state"><b>Nenhuma consulta registrada neste mês.</b><span>Assim que houver agendamentos, eles aparecerão aqui com o profissional responsável.</span></div></td></tr><?php endif;?></tbody></table></div></section>

</main></div><script src="../js/app.js"></script></body></html>
