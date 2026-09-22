<?php
header('Content-Type: application/json; charset=utf-8');require_once '../conexao.php';
$pid=filter_input(INPUT_GET,'profissional_id',FILTER_VALIDATE_INT)?:0;$data=$_GET['data']??'';
if(!$pid||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data)){http_response_code(422);echo json_encode(['ok'=>false,'mensagem'=>'Selecione profissional e data.']);exit;}
try{
 $s=$pdo->prepare('SELECT horario FROM disponibilidades WHERE profissional_id=? AND data=? AND ativo=1 ORDER BY horario');$s->execute([$pid,$data]);$datas=$s->fetchAll(PDO::FETCH_COLUMN);
 if(!$datas){$dia=(int)date('w',strtotime($data));$s=$pdo->prepare('SELECT horario FROM horarios_profissionais WHERE profissional_id=? AND dia_semana=? AND ativo=1 ORDER BY horario');$s->execute([$pid,$dia]);$datas=$s->fetchAll(PDO::FETCH_COLUMN);}
 $s=$pdo->prepare("SELECT horario FROM agendamentos WHERE profissional_id=? AND data=? AND status IN ('agendado','confirmado')");$s->execute([$pid,$data]);$ocup=array_map(fn($x)=>substr($x,0,5),$s->fetchAll(PDO::FETCH_COLUMN));
 $out=[];foreach($datas as $h){$h=substr($h,0,5);$out[]=['horario'=>$h,'ocupado'=>in_array($h,$ocup,true)];}echo json_encode(['ok'=>true,'horarios'=>$out]);
}catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'mensagem'=>'Não foi possível carregar os horários.']);}
