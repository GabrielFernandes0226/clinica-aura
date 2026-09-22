<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if (!empty($_SESSION['usuario_id'])) {
    echo json_encode(['logged'=>true,'name'=>$_SESSION['usuario_nome']??'Usuário','type'=>ucfirst($_SESSION['usuario_cargo']??'Equipe'),'area'=>'painel.php','login'=>'login.php'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!empty($_SESSION['paciente_id'])) {
    echo json_encode(['logged'=>true,'name'=>$_SESSION['paciente_nome']??'Paciente','type'=>'Paciente','area'=>'paciente/index.php','login'=>'paciente/login.php'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['logged'=>false,'name'=>'Visitante','type'=>'Não conectado','area'=>'paciente/login.php','login'=>'paciente/login.php'], JSON_UNESCAPED_UNICODE);
