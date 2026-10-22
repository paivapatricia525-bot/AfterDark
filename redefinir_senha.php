
<?php
session_start();

require_once "conexao.php";

$mensagem = "";
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");
$tokenValido = false;

if ($token !== "" && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokenHash = hash("sha256", $token);

    $consulta = $pdo->prepare(
        "SELECT id FROM usuarios
         WHERE token_recuperacao = :token
         AND token_expiracao > NOW()
         LIMIT 1"
    );

    $consulta->execute(["token" => $tokenHash]);
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    $tokenValido = (bool) $usuario;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    if (!$tokenValido) {
        $mensagem = "Este link é inválido ou expirou. Solicite uma nova recuperação.";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha precisa ter pelo menos 8 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $mensagem = "As senhas não são iguais.";
    } else {
        $novaSenha = password_hash($senha, PASSWORD_DEFAULT);

        $atualizar = $pdo->prepare(
            "UPDATE usuarios
             SET senha = :senha,
                 token_recuperacao = NULL,
                 token_expiracao = NULL
             WHERE id = :id"
        );

        $atualizar->execute([
            "senha" => $novaSenha,
            "id" => $usuario["id"]
        ]);

        $tokenValido = false;
        $mensagem = "Senha redefinida com sucesso! Você já pode entrar com a nova senha.";
    }
}

function escaparRedefinicao($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha | AfterDark</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="cabecalho">
    <a href="index.php" class="logo">
        <img src="assets/img/logo-afterdark.png" alt="Logo AfterDark">
    </a>

    <nav>
        <a href="index.php">Início</a>
        <a href="catalogo.php">Filmes</a>
        <a href="login.php">Entrar</a>
    </nav>
</header>

<main class="pagina-catalogo">
    <div class="cabecalho-catalogo">
        <h1>Redefinir senha</h1>
        <p>SEU PRÓXIMO CAPÍTULO COMEÇA AQUI</p>
        <p>Crie uma nova senha para sua conta AfterDark.</p>
    </div>

    <form action="redefinir_senha.php" method="POST" class="formulario-conta">
        <input
            type="hidden"
            name="token"
            value="<?= escaparRedefinicao($token) ?>"
        >

        <?php if ($mensagem !== ""): ?>
            <p class="<?= $tokenValido ? 'mensagem-sucesso' : 'mensagem-erro' ?>">
                <?= escaparRedefinicao($mensagem) ?>
            </p>
        <?php endif; ?>

        <?php if ($tokenValido): ?>
            <div class="campo-formulario">
                <label for="senha">Nova senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Mínimo de 8 caracteres"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="confirmar_senha">Confirmar nova senha</label>
                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Digite a senha novamente"
                    required
                >
            </div>

            <button type="submit" class="botao-principal">
                Salvar nova senha
            </button>
        <?php endif; ?>

        <p class="texto-formulario">
            <a href="login.php">Voltar para o login</a>
        </p>
    </form>
</main>

<footer class="rodape">
    <p>© <?= date("Y") ?> AfterDark — O medo nunca termina.</p>
</footer>

</body>
</html>
