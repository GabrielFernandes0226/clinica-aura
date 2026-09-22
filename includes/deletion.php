<?php
require_once __DIR__.'/audit.php';

function excluirUsuarioCompleto(PDO $pdo, int $usuarioId): string {
    $q = $pdo->prepare('SELECT id,cargo,profissional_id FROM usuarios WHERE id=?');
    $q->execute([$usuarioId]);
    $usuario = $q->fetch();
    if (!$usuario) throw new RuntimeException('Usuário não encontrado.');

    if ($usuario['cargo'] === 'diretor') throw new RuntimeException('Contas de diretor não podem ser removidas por esta tela.');
    if ((int)$usuarioId === (int)($_SESSION['usuario_id'] ?? 0)) throw new RuntimeException('Você não pode excluir seu próprio usuário.');

    if ($usuario['cargo'] === 'profissional' && !empty($usuario['profissional_id'])) {
        excluirProfissionalCompleto($pdo, (int)$usuario['profissional_id']);
        return 'Profissional e usuário vinculados foram removidos definitivamente.';
    }

    $pdo->beginTransaction();
    try {
        // Remove auditorias em que o usuário aparece como autor ou como entidade-alvo.
        $pdo->prepare("DELETE FROM auditoria WHERE usuario_id=? OR (entidade='usuario' AND entidade_id=?)")->execute([$usuarioId,$usuarioId]);
        // Permissões e redefinições de senha são removidas por ON DELETE CASCADE.
        // Agendamentos criados por esse usuário são preservados como histórico clínico; o FK apenas zera criado_por.
        $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$usuarioId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    auditar($pdo, 'REMOVEU_USUARIO_DEFINITIVAMENTE', 'sistema');
    return 'Usuário e dados de acesso foram removidos definitivamente.';
}

function excluirProfissionalCompleto(PDO $pdo, int $profissionalId): void {
    if ($profissionalId <= 0) throw new RuntimeException('Profissional inválido.');
    if ((int)($_SESSION['profissional_id'] ?? 0) === $profissionalId) {
        throw new RuntimeException('Você não pode remover o profissional vinculado à sua própria sessão.');
    }

    $q = $pdo->prepare('SELECT id FROM profissionais WHERE id=?');
    $q->execute([$profissionalId]);
    if (!$q->fetch()) throw new RuntimeException('Profissional não encontrado.');

    $q = $pdo->prepare('SELECT id FROM usuarios WHERE profissional_id=?');
    $q->execute([$profissionalId]);
    $usuarios = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        if ($usuarios) {
            $ph = implode(',', array_fill(0, count($usuarios), '?'));
            $params = array_merge($usuarios,$usuarios);
            $pdo->prepare("DELETE FROM auditoria WHERE usuario_id IN ($ph) OR (entidade='usuario' AND entidade_id IN ($ph))")->execute($params);
        }
        $pdo->prepare("DELETE FROM auditoria WHERE entidade='profissional' AND entidade_id=?")->execute([$profissionalId]);
        // As consultas desse profissional são dados diretamente vinculados ao cadastro removido.
        $pdo->prepare('DELETE FROM agendamentos WHERE profissional_id=?')->execute([$profissionalId]);
        // Ao remover os usuários vinculados, permissões e tokens de redefinição caem por cascade.
        $pdo->prepare('DELETE FROM usuarios WHERE profissional_id=?')->execute([$profissionalId]);
        // Horários recorrentes e disponibilidades caem por cascade no profissional.
        $pdo->prepare('DELETE FROM profissionais WHERE id=?')->execute([$profissionalId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    auditar($pdo, 'REMOVEU_PROFISSIONAL_DEFINITIVAMENTE', 'sistema');
}
