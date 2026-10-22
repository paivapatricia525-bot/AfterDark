
<?php
session_start();

// Encerra a sessão atual para permitir entrar com outra conta.
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destrói a sessão atual.
session_destroy();

// Redireciona para a página de login.
header("Location: login.php");
exit;
