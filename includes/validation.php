<?php
function somenteDigitos(?string $valor): string {
    return preg_replace('/\D+/', '', (string)$valor) ?? '';
}

function cpfValido(?string $cpf): bool {
    $cpf = somenteDigitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) $soma += ((int)$cpf[$i]) * (($t + 1) - $i);
        $digito = (10 * $soma) % 11;
        if ($digito === 10) $digito = 0;
        if ((int)$cpf[$t] !== $digito) return false;
    }
    return true;
}

function telefoneValido(?string $telefone): bool {
    $n = somenteDigitos($telefone);
    // DDD: 2 dígitos sem zero à esquerda. Fixo: começa de 2 a 5. Celular: começa em 9.
    if (strlen($n) === 10) return (bool)preg_match('/^[1-9]{2}[2-5]\d{7}$/', $n);
    if (strlen($n) === 11) return (bool)preg_match('/^[1-9]{2}9\d{8}$/', $n);
    return false;
}

function formatarCpf(?string $cpf): string {
    $n = somenteDigitos($cpf);
    if (strlen($n) !== 11) return (string)$cpf;
    return substr($n,0,3).'.'.substr($n,3,3).'.'.substr($n,6,3).'-'.substr($n,9,2);
}

function formatarTelefone(?string $telefone): string {
    $n = somenteDigitos($telefone);
    if (strlen($n) === 11) return '('.substr($n,0,2).') '.substr($n,2,5).'-'.substr($n,7,4);
    if (strlen($n) === 10) return '('.substr($n,0,2).') '.substr($n,2,4).'-'.substr($n,6,4);
    return (string)$telefone;
}
