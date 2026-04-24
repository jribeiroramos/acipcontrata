<?php
// Inicia a sessão para poder destruí-la
session_start();

// Remove todas as variáveis de sessão
$_SESSION = array();

// Destrói a sessão no servidor
session_destroy();

// Redireciona para a página inicial (Login)
header("Location: index.php");
exit;
