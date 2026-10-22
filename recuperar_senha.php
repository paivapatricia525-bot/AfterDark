
<?php
session_start();

require_once "conexao.php";
require_once "config.php";

$mensagem = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $mensagem = "Digite seu e-mail para continuar.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Digite um endereço de e-mail válido.";
    } else {
        $consulta = $pdo->prepare(
            "SELECT id, nome, email FROM usuarios WHERE email = :email LIMIT 1"
        );

        $consulta->execute(["email" => $email]);
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            try {
                // Gera um token seguro e define validade de 1 hora.
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash("sha256", $token);

                $expiracao = date("Y-m-d H:i:s", time() + 3600);

                $atualizar = $pdo->prepare(
                    "UPDATE usuarios
                     SET token_recuperacao = :token,
                         token_expiracao = :expiracao
                     WHERE id = :id"
                );

                $atualizar->execute([
                    "token" => $tokenHash,
                    "expiracao" => $expiracao,
                    "id" => $usuario["id"]
                ]);

                $link = "http://localhost/AfterDark/redefinir_senha.php?token=" . urlencode($token);

                $dados = [
                    "from" => "AfterDark <onboarding@resend.dev>",
                    "to" => [$usuario["email"]],
                    "subject" => "Recuperação de senha | AfterDark",
                    "html" => "
                        <div style='font-family:Arial,sans-serif;background:#101010;color:#f5f5f5;padding:30px;border-radius:12px;'>
                            <h1 style='color:#8B1E2D;'>AFTERDARK</h1>
                            <h2>Recuperação de senha</h2>
                            <p>Olá, " . htmlspecialchars($usuario["nome"], ENT_QUOTES, "UTF-8") . "!</p>
                            <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                            <p>Para criar uma nova senha, clique no botão abaixo. O link expira em 1 hora.</p>
                            <p style='margin:30px 0;'>
                                <a href='" . htmlspecialchars($link, ENT_QUOTES, "UTF-8") . "'
                                   style='background:#8B1E2D;color:white;padding:14px 22px;text-decoration:none;border-radius:6px;'>
                                   Redefinir minha senha
                                </a>
                            </p>
                            <p>Se você não solicitou esta alteração, ignore este e-mail.</p>
                            <p style='color:#999;font-size:12px;'>AfterDark — O medo nunca termina.</p>
                        </div>
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
                } elseif ($status < 200 || $status >= 300) {
                    error_log("Erro Resend HTTP " . $status . ": " . $resposta);
                }

            } catch (Throwable $erro) {
                error_log("Erro na recuperação de senha: " . $erro->getMessage());
            }
        }

        // Mensagem genérica para não revelar se o e-mail está cadastrado.
        $mensagem = "Se o e-mail estiver cadastrado, você receberá as instruções para redefinir sua senha.";
    }
}

function escaparRecuperacao($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha | AfterDark</title>
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
        <h1>Recuperar senha</h1>
        <p>UM NOVO COMEÇO NO ESCURO</p>
        <p>Informe o e-mail utilizado no seu cadastro.</p>
    </div>

    <form action="recuperar_senha.php" method="POST" class="formulario-conta">
        <?php if ($mensagem !== ""): ?>
            <p class="mensagem-sucesso">
                <?= escaparRecuperacao($mensagem) ?>
            </p>
        <?php endif; ?>

        <div class="campo-formulario">
            <label for="email">E-mail cadastrado</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="seuemail@exemplo.com"
                value="<?= escaparRecuperacao($email) ?>"
                required
            >
        </div>

        <button type="submit" class="botao-principal">
            Enviar instruções
        </button>

        <p class="texto-formulario">
            Lembrou da senha? <a href="login.php">Voltar para o login</a>
        </p>
    </form>
</main>

<footer class="rodape">
    <p>© <?= date("Y") ?> AfterDark — O medo nunca termina.</p>
</footer>

</body>
</html>
