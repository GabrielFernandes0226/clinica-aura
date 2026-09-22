<?php
session_start();
require_once 'conexao.php';
require_once 'includes/flash.php';
if (empty($_SESSION['usuario_id'])) { flash('error','Faça login para continuar.'); header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: admin/index.php'); exit; }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { flash('error','Agendamento inválido.'); header('Location: admin/index.php'); exit; }
try {
    $stmt=$pdo->prepare("UPDATE agendamentos SET status='cancelado' WHERE id=? AND status IN ('agendado','confirmado')");
    $stmt->execute([$id]);
    flash($stmt->rowCount() ? 'success':'warning', $stmt->rowCount() ? 'Agendamento cancelado com sucesso.':'O agendamento já estava cancelado ou não existe.');
} catch(Throwable $e) { flash('error','Não foi possível cancelar o agendamento.'); }
header('Location: admin/index.php'); exit;
