<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function usuarioLogado(): bool { return !empty($_SESSION['usuario_id']); }
function cargoAtual(): string { return $_SESSION['usuario_cargo'] ?? ''; }
function isDiretor(): bool { return cargoAtual()==='diretor'; }
function pode(string $permissao): bool {
    if (isDiretor()) return true;
    $p = $_SESSION['permissoes'] ?? [];
    return in_array($permissao,$p,true);
}
function exigirLogin(string $base='../'): void {
    if (!usuarioLogado()) { $_SESSION['flash']=['tipo'=>'error','mensagem'=>'Faça login para continuar.']; header('Location: '.$base.'login.php'); exit; }
}
function exigirCargo(array $cargos,string $base='../'): void {
    exigirLogin($base);
    if (!in_array(cargoAtual(),$cargos,true)) { $_SESSION['flash']=['tipo'=>'error','mensagem'=>'Você não possui permissão para acessar esta área.']; header('Location: '.$base.'painel.php'); exit; }
}
function exigirPermissao(string $permissao,string $base='../'): void {
    exigirLogin($base);
    if (!pode($permissao)) { $_SESSION['flash']=['tipo'=>'error','mensagem'=>'Seu usuário não possui essa permissão.']; header('Location: '.$base.'painel.php'); exit; }
}
function carregarPermissoes(PDO $pdo,int $uid): array {
    $s=$pdo->prepare('SELECT permissao FROM usuario_permissoes WHERE usuario_id=?'); $s->execute([$uid]); return $s->fetchAll(PDO::FETCH_COLUMN);
}
