<?php
/**
 * Espaço Aura - processador de lembretes por e-mail.
 * Execute a cada minuto pelo Agendador de Tarefas do Windows / cron.
 * No XAMPP, configure mail()/SMTP antes de produção.
 */
require_once __DIR__ . '/../conexao.php';
date_default_timezone_set('America/Sao_Paulo');
$agora = new DateTimeImmutable();
$limite = $agora->modify('+7 days');
$sql = "SELECT a.id,a.nome,a.servico,a.data,a.horario,a.lembrete_minutos,p.email,pr.nome profissional
        FROM agendamentos a
        JOIN pacientes p ON p.id=a.paciente_id
        JOIN profissionais pr ON pr.id=a.profissional_id
        WHERE a.status IN ('agendado','confirmado')
          AND a.lembrete_minutos > 0
          AND a.lembrete_enviado_em IS NULL
          AND p.ativo=1
          AND TIMESTAMP(a.data,a.horario) BETWEEN NOW() AND ?
        ORDER BY a.data,a.horario";
$st=$pdo->prepare($sql);$st->execute([$limite->format('Y-m-d H:i:s')]);
foreach($st->fetchAll() as $a){
    $consulta=new DateTimeImmutable($a['data'].' '.$a['horario']);
    $enviarEm=$consulta->modify('-'.(int)$a['lembrete_minutos'].' minutes');
    if($agora < $enviarEm) continue;
    $assunto='Lembrete de consulta - Espaço Aura';
    $quando=$consulta->format('d/m/Y \à\s H:i');
    $mensagem="Olá, {$a['nome']}!\n\nEste é um lembrete da sua consulta no Espaço Aura.\nServiço: {$a['servico']}\nProfissional: {$a['profissional']}\nData e horário: {$quando}\n\nTelefone da clínica: (11) 3683-3049\n\nEspaço Aura";
    $headers="From: Espaco Aura <nao-responda@espacoaura.local>\r\nContent-Type: text/plain; charset=UTF-8";
    $ok=false;$erro=null;
    if(defined('AURA_DEV') && AURA_DEV){
        $dir=__DIR__.'/../storage';if(!is_dir($dir))mkdir($dir,0775,true);
        $ok=(bool)file_put_contents($dir.'/lembretes-dev.log',"\n--- ".date('c')." ---\nPara: {$a['email']}\n{$mensagem}\n",FILE_APPEND);
    }else{
        $ok=@mail($a['email'],$assunto,$mensagem,$headers);
        if(!$ok)$erro='Falha no envio pelo servidor de e-mail.';
    }
    $up=$pdo->prepare('UPDATE agendamentos SET lembrete_enviado_em=NOW(),lembrete_status=?,lembrete_erro=? WHERE id=? AND lembrete_enviado_em IS NULL');
    $up->execute([$ok?'enviado':'erro',$erro,$a['id']]);
    echo ($ok?'ENVIADO':'ERRO')." #{$a['id']} {$a['email']}\n";
}
