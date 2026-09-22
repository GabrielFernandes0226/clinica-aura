<?php
/**
 * Preparação para WhatsApp.
 *
 * Para enviar mensagens automaticamente de verdade, é necessário
 * conectar uma API de WhatsApp (por exemplo, WhatsApp Business Platform
 * ou outro provedor).
 *
 * Nesta versão o método apenas monta a mensagem.
 */
function mensagemWhatsApp(array $agendamento): string
{
    $data = date('d/m/Y', strtotime($agendamento['data']));
    $hora = substr($agendamento['horario'], 0, 5);

    return "Olá, {$agendamento['nome']}! Seu atendimento de {$agendamento['servico']} "
         . "está agendado para {$data} às {$hora}.";
}
?>