
<?php
session_start();
require_once "conexao.php";

$erro = "";
$email = "";

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {
        $erro = "Preencha o e-mail e a senha.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } else {
        $consulta = $pdo->prepare(
            "SELECT id, nome, email, senha
             FROM usuarios
             WHERE email = :email
             LIMIT 1"
        );

        $consulta->execute(["email" => $email]);
        $usuario = $consulta->fetch();

        if ($usuario && password_verify($senha, $usuario["senha"])) {
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nome"] = $usuario["nome"];
            $_SESSION["usuario_email"] = $usuario["email"];

            header("Location: index.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}

function escaparLogin($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | AfterDark</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="cabecalho">
        <a href="index.php" class="logo">
            <img src="assets/img/logo-afterdark.png" alt="AfterDark">
        </a>

        <nav class="menu">
            <a href="index.php">Início</a>
            <a href="catalogo.php">Filmes</a>
            <a href="cadastro.php">Criar conta</a>
        </nav>
    </header>

    <main class="pagina-catalogo">
        <section class="cabecalho-catalogo">
            <p class="subtitulo">O MEDO ESPERA POR VOCÊ</p>
            <h1>Bem-vindo de volta</h1>
            <p>Entre na sua conta e continue explorando o lado sombrio do cinema.</p>
        </section>

        <form method="POST" action="login.php" class="formulario-conta">

            <?php if ($erro !== ""): ?>
                <p class="mensagem-erro">
                    <?= escaparLogin($erro) ?>
                </p>
            <?php endif; ?>

            <div class="campo-formulario">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    value="<?= escaparLogin($email) ?>"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="botao-login">
                Entrar
            </button>

            <p>
                Esqueceu sua senha?
                <a href="recuperar_senha.php">Recuperar acesso</a>
            </p>

            <p>
                Ainda não tem uma conta?
                <a href="cadastro.php">Cadastre-se</a>
            </p>

        </form>
    </main>

    <footer class="rodape">
        <p>&copy; 2026 AfterDark. O cinema além do medo.</p>
    </footer>

</body>
</html>
