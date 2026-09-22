<?php
function renderDiretorSidebar(string $active='dashboard', string $context='diretor'): void {
    $isAdmin = $context === 'admin';
    $links = [
        'dashboard' => [$isAdmin ? '../diretor/index.php' : 'index.php', 'Dashboard', '⌂'],
        'usuarios' => [$isAdmin ? '../diretor/usuarios.php' : 'usuarios.php', 'Usuários e acessos', '♙'],
        'funcionarios' => [$isAdmin ? '../diretor/funcionarios.php' : 'funcionarios.php', 'Dados dos funcionários', '▣'],
        'relatorios' => [$isAdmin ? '../diretor/relatorios.php' : 'relatorios.php', 'Consultas / planilha', '▤'],
        'profissionais' => [$isAdmin ? 'profissionais.php' : '../admin/profissionais.php', 'Profissionais', '♧'],
        'horarios' => [$isAdmin ? 'horarios.php' : '../admin/horarios.php', 'Horários', '◷'],
        'auditoria' => [$isAdmin ? '../diretor/auditoria.php' : 'auditoria.php', 'Auditoria', '◎'],
    ];
    $logo = $isAdmin ? '../img/logo-aura-branca.png' : '../img/logo-aura-branca.png';
    $logout = $isAdmin ? '../logout.php' : '../logout.php';
    echo '<aside class="sidebar director-sidebar" aria-label="Navegação da diretoria">';
    echo '<a href="'.htmlspecialchars($links['dashboard'][0]).'" class="side-logo"><img src="'.htmlspecialchars($logo).'" alt="Espaço Aura"></a>';
    echo '<div class="director-nav-title"><small>DIRETORIA</small><span>Central executiva</span></div>';
    echo '<nav class="director-nav">';
    foreach ($links as $key=>$item) {
        [$href,$label,$icon]=$item;
        $class=$key===$active?' class="active"':'';
        echo '<a'.$class.' href="'.htmlspecialchars($href).'" aria-current="'.($key===$active?'page':'false').'"><span class="nav-icon" aria-hidden="true">'.$icon.'</span><span>'.$label.'</span></a>';
    }
    echo '</nav>';
    echo '<div class="director-sidebar-foot"><span class="sidebar-status-dot"></span><div><b>Sistema online</b><small>Menu sempre visível</small></div></div>';
    echo '<a class="director-logout" href="'.htmlspecialchars($logout).'"><span aria-hidden="true">↪</span> Sair</a>';
    echo '</aside>';
}
