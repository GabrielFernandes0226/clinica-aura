<?php
session_start();
require_once 'conexao.php';
require_once 'includes/flash.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS redefinicoes_senha_usuarios (id BIGINT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expira_em DATETIME NOT NULL,usado_em DATETIME NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,INDEX idx_reset_usuario(usuario_id),INDEX idx_reset_usuario_expira(expira_em)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL);
  if($email){
    $s=$pdo->prepare('SELECT id,nome FROM usuarios WHERE email=? AND ativo=1 LIMIT 1');$s->execute([$email]);
    if($u=$s->fetch()){
      $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$exp=date('Y-m-d H:i:s',time()+3600);
      $pdo->prepare('DELETE FROM redefinicoes_senha_usuarios WHERE usuario_id=?')->execute([$u['id']]);
      $pdo->prepare('INSERT INTO redefinicoes_senha_usuarios(usuario_id,token_hash,expira_em) VALUES(?,?,?)')->execute([$u['id'],$hash,$exp]);
      $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$base=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
      $link=$scheme.'://'.$_SERVER['HTTP_HOST'].$base.'/redefinir-senha.php?token='.urlencode($token);
      $assunto='Redefinição de senha - Espaço Aura';
      $msg="Olá {$u['nome']},\n\nUse o link abaixo para redefinir sua senha de acesso à equipe. Ele expira em 1 hora:\n$link\n\nSe você não solicitou, ignore esta mensagem.";
      $headers="From: Espaço Aura <nao-responda@espacoaura.com.br>\r\nContent-Type: text/plain; charset=UTF-8";
      @mail($email,$assunto,$msg,$headers);
      if(defined('AURA_DEV')&&AURA_DEV)file_put_contents(__DIR__.'/storage/reset-mails.log',date('c')." | equipe | $email | $link\n",FILE_APPEND);
    }
  }
  flash('success','Se o e-mail estiver cadastrado e ativo, enviaremos um link de redefinição válido por 1 hora.');
  header('Location: esqueci-senha.php');exit;
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recuperar senha | Espaço Aura</title><link rel="icon" type="image/png" sizes="512x512" href="img/favicon-aura.png"><link rel="shortcut icon" href="img/favicon.ico"><link rel="stylesheet" href="css/style.css"></head><body><header class="header"><div class="container nav"><a href="index.php" class="brand"><img src="img/logo-aura-oficial.png" alt="Espaço Aura"></a><a href="login.php" class="back-link">← Voltar</a></div></header><main class="form-page"><div class="form-container"><p class="eyebrow">PORTAL DA EQUIPE</p><h1>Esqueceu sua senha?</h1><p class="muted">Informe o e-mail do seu acesso. Você receberá um link para criar uma nova senha.</p><?php mostrarFlash();?><form method="post" class="form-card"><label>E-mail<input type="email" name="email" autocomplete="username" required></label><button class="btn btn-full">Enviar link de redefinição</button><p style="text-align:center;margin-top:16px"><a href="login.php">Voltar ao login</a></p></form></div></main><script src="js/app.js"></script></body></html>
