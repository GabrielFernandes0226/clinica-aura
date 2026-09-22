<?php
session_start(); require_once 'conexao.php'; require_once 'includes/flash.php'; require_once 'includes/rbac.php';
if(usuarioLogado()){header('Location: painel.php');exit;}
$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL);$senha=$_POST['senha']??'';
 if(!$email||$senha==='')$erro='Informe e-mail e senha.'; else try{
  $s=$pdo->prepare('SELECT id,nome,email,senha,cargo,ativo,profissional_id FROM usuarios WHERE email=? LIMIT 1');$s->execute([$email]);$u=$s->fetch();
  if($u && $u['ativo'] && password_verify($senha,$u['senha'])){session_regenerate_id(true);$_SESSION['usuario_id']=(int)$u['id'];$_SESSION['usuario_nome']=$u['nome'];$_SESSION['usuario_cargo']=$u['cargo'];$_SESSION['profissional_id']=$u['profissional_id'];$_SESSION['permissoes']=carregarPermissoes($pdo,(int)$u['id']);header('Location: painel.php');exit;}
  $erro='E-mail ou senha inválidos.';
 }catch(Throwable $e){$erro='Não foi possível entrar agora. Tente novamente.';}
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Espaço Aura</title><link rel="stylesheet" href="css/style.css"></head><body><header class="header"><div class="container nav"><a href="index.php" class="brand"><img src="img/logo-aura-oficial.png" alt="Espaço Aura"></a><a href="index.php" class="back-link">← Voltar</a></div></header><main class="form-page"><div class="form-container"><p class="eyebrow">PORTAL DA EQUIPE</p><h1>Acesso ao sistema</h1><?php mostrarFlash(); if($erro):?><div class="alert error" data-popup="1"><?=htmlspecialchars($erro)?></div><?php endif;?><form method="post" class="form-card"><label>E-mail</label><input type="email" name="email" autocomplete="username" required><label>Senha</label><input type="password" name="senha" autocomplete="current-password" required><button class="btn btn-full">Entrar</button></form></div></main><script src="js/app.js"></script></body></html>
