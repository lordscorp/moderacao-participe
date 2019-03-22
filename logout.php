<?php
// Inicia a sessão
session_start();
 
// Limpa todas as variáveis de sessão
$_SESSION = array();
 
// Destrói a sessão.
session_destroy();
 
// Redireciona à página de login
header("location: login.php");
exit;
?>