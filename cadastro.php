<?php
session_start();

require_once "conexao.php";
require_once "config.php";

$erro = "";
$sucesso = "";
$nome = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    if ($nome === "" || $email === "" || $senha === "" || $confirmarSenha === "") {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha precisa ter pelo menos 8 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $erro = "As senhas não coincidem.";
    } else {
        try {
            $consulta = $pdo->prepare(
                "SELECT id FROM usuarios WHERE email = :email"
            );
            $consulta->execute(["email" => $email]);

            if ($consulta->fetch()) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $inserir = $pdo->prepare(
                    "INSERT INTO usuarios (nome, email, senha)
                     VALUES (:nome, :email, :senha)"
                );

                $inserir->execute([
                    "nome" => $nome,
                    "email" => $email,
                    "senha" => $senhaHash
                ]);

               
$nomeCadastrado = $nome;
$emailCadastrado = $email;

if (enviarNotificacaoCadastro($nomeCadastrado, $emailCadastrado)) {
    $sucesso = "Cadastro realizado! A notificação por e-mail foi enviada.";
} else {
    $sucesso = "Cadastro realizado! Porém, não foi possível enviar a notificação por e-mail.";
}

$nome = "";
$email = "";

            }
        } catch (PDOException $e) {
            $erro = "Não foi possível concluir o cadastro. Tente novamente.";
        }
    }
}


function enviarNotificacaoCadastro($nome, $email) {
    $dados = [
        "from" => "AfterDark <onboarding@resend.dev>",
        "to" => [EMAIL_ADMIN],
        "subject" => "Novo cadastro no AfterDark",
        "html" => "
            <h2>Novo usuário cadastrado!</h2>
            <p>Uma nova conta foi criada no AfterDark.</p>
            <p><strong>Nome:</strong> " .
                htmlspecialchars($nome, ENT_QUOTES, "UTF-8") .
            "</p>
            <p><strong>E-mail:</strong> " .
                htmlspecialchars($email, ENT_QUOTES, "UTF-8") .
            "</p>
        "
    ];

    $curl = curl_init("https://api.resend.com/emails");

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . RESEND_API_KEY,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_TIMEOUT => 15
    ]);

   $resposta = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$erroCurl = curl_error($curl);

curl_close($curl);

if ($resposta === false) {
    error_log("Erro cURL Resend: " . $erroCurl);
    return false;
}

if ($status < 200 || $status >= 300) {
    error_log("Erro Resend HTTP " . $status . ": " . $resposta);
    return false;
}

return true;
}


function escaparCadastro($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta | AfterDark</title>
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
            <a href="login.php">Entrar</a>
        </nav>
    </header>

    <main class="pagina-catalogo">
        <section class="cabecalho-catalogo">
            <p class="subtitulo">ENTRE NA ESCURIDÃO</p>
            <h1>Crie sua conta</h1>
            <p>Cadastre-se para aproveitar sua experiência no AfterDark.</p>
        </section>

        <form method="POST" action="cadastro.php" class="formulario-conta">
            <?php if ($erro !== ""): ?>
                <p class="mensagem-erro"><?= escaparCadastro($erro) ?></p>
            <?php endif; ?>

            <?php if ($sucesso !== ""): ?>
                <p class="mensagem-sucesso"><?= escaparCadastro($sucesso) ?></p>
                <p><a href="login.php">Ir para o login</a></p>
            <?php endif; ?>

            <div class="campo-formulario">
                <label for="nome">Nome completo</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="100"
                    autocomplete="name"
                    value="<?= escaparCadastro($nome) ?>"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    value="<?= escaparCadastro($email) ?>"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="senha">Senha (mínimo 8 caracteres)</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="confirmar_senha">Confirmar senha</label>
                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="botao-login">
                Criar conta
            </button>

            <p>Já tem uma conta? <a href="login.php">Entre aqui</a>.</p>
        </form>
    </main>

    <footer class="rodape">
        <p>&copy; 2026 AfterDark. O cinema além do medo.</p>
    </footer>

</body>
</html>
