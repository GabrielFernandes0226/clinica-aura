<?php
if(session_status()===PHP_SESSION_NONE)session_start();
function flash(string $tipo,string $mensagem):void{$_SESSION['flash']=['tipo'=>$tipo,'mensagem'=>$mensagem];}
function mostrarFlash():void{if(empty($_SESSION['flash']))return;$f=$_SESSION['flash'];unset($_SESSION['flash']);$tipo=in_array($f['tipo'],['success','error','warning','info'],true)?$f['tipo']:'info';echo '<div class="alert '.htmlspecialchars($tipo).'" data-popup="1">'.htmlspecialchars($f['mensagem']).'</div>';}
