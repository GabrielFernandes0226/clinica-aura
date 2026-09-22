<?php
session_start(); require_once 'includes/rbac.php'; exigirLogin('');
$map=['diretor'=>'diretor/index.php','administrador'=>'admin/index.php','recepcionista'=>'recepcao/index.php','profissional'=>'profissional/index.php'];
header('Location: '.($map[cargoAtual()]??'login.php'));exit;
