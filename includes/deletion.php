<?php
require_once __DIR__.'/audit.php';

/**
 * Executa uma limpeza de tabela auxiliar sem derrubar a exclusão em instalações
 * antigas nas quais a tabela ainda não existe. Outros erros continuam sendo lançados.
 */
function auraExcluirOpcional(PDO $pdo, string $sql, array $params = []): void {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
    } catch (PDOException $e) {
        // 42S02 = tabela/base inexistente. Somente esse caso é tolerado.
        if ((string)$e->getCode() !== '42S02') throw $e;
    }
}

function excluirUsuarioCompleto(PDO $pdo, int $usuarioId): string {
    $q = $pdo->prepare('SELECT id,cargo,profissional_id FROM usuarios WHERE id=?');
    $q->execute([$usuarioId]);
    $usuario = $q->fetch();
    if (!$usuario) throw new RuntimeException('Usuário não encontrado.');

    if ($usuario['cargo'] === 'diretor') throw new RuntimeException('Contas de diretor não podem ser removidas por esta tela.');
    if ($usuarioId === (int)($_SESSION['usuario_id'] ?? 0)) throw new RuntimeException('Você não pode excluir seu próprio usuário.');

    if ($usuario['cargo'] === 'profissional' && !empty($usuario['profissional_id'])) {
        excluirProfissionalCompleto($pdo, (int)$usuario['profissional_id']);
        return 'Profissional, usuário e todos os dados vinculados foram removidos definitivamente.';
    }

    $pdo->beginTransaction();
    try {
        // Consultas criadas por esse usuário também são dados vinculados ao cadastro removido.
        // Os campos de lembrete fazem parte da própria linha de agendamento e são apagados junto.
        $pdo->prepare('DELETE FROM agendamentos WHERE criado_por=?')->execute([$usuarioId]);

        // Exclusão explícita além do ON DELETE CASCADE para manter o comportamento previsível
        // mesmo em bancos antigos que tenham sido criados com relacionamentos diferentes.
        auraExcluirOpcional($pdo, 'DELETE FROM usuario_permissoes WHERE usuario_id=?', [$usuarioId]);
        auraExcluirOpcional($pdo, 'DELETE FROM redefinicoes_senha_usuarios WHERE usuario_id=?', [$usuarioId]);
        auraExcluirOpcional($pdo, "DELETE FROM auditoria WHERE usuario_id=? OR (entidade='usuario' AND entidade_id=?)", [$usuarioId, $usuarioId]);

        $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$usuarioId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    auditar($pdo, 'REMOVEU_USUARIO_DEFINITIVAMENTE', 'sistema');
    return 'Usuário e todos os dados vinculados foram removidos definitivamente.';
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
        // Remove todas as consultas e respectivos dados de lembrete ligados ao profissional.
        $pdo->prepare('DELETE FROM agendamentos WHERE profissional_id=?')->execute([$profissionalId]);

        if ($usuarios) {
            $ph = implode(',', array_fill(0, count($usuarios), '?'));

            // Se o usuário profissional tiver criado alguma consulta de outro profissional,
            // ela também é removida porque está vinculada ao usuário que será excluído.
            $pdo->prepare("DELETE FROM agendamentos WHERE criado_por IN ($ph)")->execute($usuarios);

            auraExcluirOpcional($pdo, "DELETE FROM usuario_permissoes WHERE usuario_id IN ($ph)", $usuarios);
            auraExcluirOpcional($pdo, "DELETE FROM redefinicoes_senha_usuarios WHERE usuario_id IN ($ph)", $usuarios);
            $params = array_merge($usuarios, $usuarios);
            auraExcluirOpcional($pdo, "DELETE FROM auditoria WHERE usuario_id IN ($ph) OR (entidade='usuario' AND entidade_id IN ($ph))", $params);
        }

        auraExcluirOpcional($pdo, "DELETE FROM auditoria WHERE entidade='profissional' AND entidade_id=?", [$profissionalId]);

        // Exclusões explícitas deixam a rotina independente do estado dos ON DELETE CASCADE.
        $pdo->prepare('DELETE FROM horarios_profissionais WHERE profissional_id=?')->execute([$profissionalId]);
        $pdo->prepare('DELETE FROM disponibilidades WHERE profissional_id=?')->execute([$profissionalId]);
        $pdo->prepare('DELETE FROM usuarios WHERE profissional_id=?')->execute([$profissionalId]);
        $pdo->prepare('DELETE FROM profissionais WHERE id=?')->execute([$profissionalId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    auditar($pdo, 'REMOVEU_PROFISSIONAL_DEFINITIVAMENTE', 'sistema');
}
