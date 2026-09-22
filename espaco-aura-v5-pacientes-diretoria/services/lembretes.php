<?php
require_once __DIR__ . "/../conexao.php";

/*
 * Busca consultas para amanhã.
 * A parte de envio fica separada para quando uma API de WhatsApp/e-mail
 * for configurada.
 */
$amanha = date('Y-m-d', strtotime('+1 day'));

$stmt = $pdo->prepare("
    SELECT a.*, p.nome AS profissional
    FROM agendamentos a
    JOIN profissionais p ON p.id = a.profissional_id
    WHERE a.data = ?
      AND a.status IN ('agendado','confirmado')
    ORDER BY a.horario
");
$stmt->execute([$amanha]);

foreach ($stmt->fetchAll() as $a) {
    echo "Lembrete: {$a['nome']} - {$a['data']} {$a['horario']} - {$a['telefone']}\n";
}
?>