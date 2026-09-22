<?php session_start();unset($_SESSION['paciente_id'],$_SESSION['paciente_nome']);header('Location: login.php');exit;
