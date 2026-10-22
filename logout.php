
<?php
session_start();

// Encerra os dados da sessão do usuário.
$_SESSION = [];

// Destrói a sessão atual.
session_destroy();

// Volta para a página de login.
header("Location: login.php");
exit;
?>
