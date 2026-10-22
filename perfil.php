<?php
session_start();

require_once "conexao.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$mensagem = "";
$tipoMensagem = "";

function escaparPerfil($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Busca os dados do usuário
$sql = "SELECT nome, email, criado_em
        FROM usuarios
        WHERE id = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se a conta ainda existe
if (!$usuario) {
    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit;
}

// Processa a atualização do perfil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($nome === "" || $email === "") {
        $mensagem = "Preencha todos os campos.";
        $tipoMensagem = "erro";

    } elseif (mb_strlen($nome) > 100) {
        $mensagem = "O nome deve ter no máximo 100 caracteres.";
        $tipoMensagem = "erro";

    } elseif (
        strlen($email) > 190 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $mensagem = "Digite um e-mail válido.";
        $tipoMensagem = "erro";

    } else {
        // Verifica se outro usuário já utiliza esse e-mail
        $sql = "SELECT id
                FROM usuarios
                WHERE email = ? AND id != ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $usuarioId]);

        if ($stmt->fetch()) {
            $mensagem = "Esse e-mail já está sendo utilizado.";
            $tipoMensagem = "erro";

        } else {
            // Atualiza os dados no banco
            $sql = "UPDATE usuarios
                    SET nome = ?, email = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $usuarioId]);

            // Atualiza as mesmas sessões utilizadas pelo login
            $_SESSION["usuario_nome"] = $nome;
            $_SESSION["usuario_email"] = $email;

            // Atualiza os dados exibidos
            $usuario["nome"] = $nome;
            $usuario["email"] = $email;

            $mensagem = "Suas informações foram atualizadas com sucesso.";
            $tipoMensagem = "sucesso";
        }
    }
}

// Conta os filmes favoritos do usuário
$sql = "SELECT COUNT(*)
        FROM favoritos
        WHERE usuario_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);
$totalFavoritos = (int) $stmt->fetchColumn();

// Formata a data de cadastro
$dataCadastro = "Não disponível";

if (!empty($usuario["criado_em"])) {
    $timestamp = strtotime($usuario["criado_em"]);

    if ($timestamp !== false) {
        $dataCadastro = date("d/m/Y", $timestamp);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu perfil | AfterDark</title>

   <link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/perfil.css">

   
</head>

<body>

<header class="cabecalho">
    <a href="index.php" class="logo">
        <img src="assets/img/logo-afterdark.png" alt="AfterDark">
    </a>

    <nav class="menu">
        <a href="index.php">Início</a>
        <a href="catalogo.php">Filmes</a>
        <a href="favoritos.php">Minha lista</a>
        <a href="perfil.php" aria-current="page">Meu perfil</a>
    </nav>
</header>

<main class="perfil-pagina">

    <section class="perfil-introducao">
        <p class="subtitulo">SUA JORNADA PELO HORROR</p>

        <h1>Meu perfil<span style="color: #C34A5A;">.</span></h1>

        <p>Seu espaço pessoal no universo AfterDark.</p>
    </section>

    <section class="perfil-resumo">

        <article class="perfil-cartao">
            <div class="perfil-cartao-topo">
                <div class="perfil-icone" aria-hidden="true">&#9786;</div>

                <div>
                    <small>IDENTIDADE</small>
                    <strong><?= escaparPerfil($usuario["nome"]) ?></strong>
                </div>
            </div>

            <p>Este é o nome associado à sua conta AfterDark.</p>
        </article>

        <article class="perfil-cartao">
            <div class="perfil-cartao-topo">
                <div class="perfil-icone" aria-hidden="true">&#9733;</div>

                <div>
                    <small>SUA COLEÇÃO</small>

                    <strong>
                        <?= $totalFavoritos ?>
                        <?= $totalFavoritos === 1 ? "filme" : "filmes" ?>
                    </strong>
                </div>
            </div>

            <p>Filmes que você guardou na sua lista de favoritos.</p>

            <a
                href="favoritos.php"
                class="perfil-botao perfil-botao-secundario"
                style="margin-top: 15px;"
            >
                Ver minha lista
            </a>
        </article>

    </section>

    <section class="perfil-conteudo">

        <div class="perfil-painel">

            <h2>Informações da conta</h2>

            <p class="perfil-painel-descricao">
                Mantenha seus dados atualizados para personalizar sua experiência.
            </p>

            <?php if ($mensagem !== ""): ?>
                <div
                    class="perfil-mensagem <?= $tipoMensagem === "sucesso" ? "sucesso" : "erro" ?>"
                    role="status"
                >
                    <?= escaparPerfil($mensagem) ?>
                </div>
            <?php endif; ?>

            <form action="perfil.php" method="POST">

                <div class="campo-formulario">
                    <label for="nome">Nome completo</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        autocomplete="name"
                        value="<?= escaparPerfil($_POST["nome"] ?? $usuario["nome"]) ?>"
                        required
                    >
                </div>

                <div class="campo-formulario">
                    <label for="email">Endereço de e-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="190"
                        autocomplete="email"
                        value="<?= escaparPerfil($_POST["email"] ?? $usuario["email"]) ?>"
                        required
                    >
                </div>

                <div class="perfil-botoes">
                    <button
                        type="submit"
                        class="perfil-botao perfil-botao-principal"
                    >
                        Salvar alterações
                    </button>

                    <a
                        href="catalogo.php"
                        class="perfil-botao perfil-botao-secundario"
                    >
                        Explorar filmes
                    </a>
                </div>

            </form>

            <section class="perfil-acoes-conta">
                <h2>Opções da conta</h2>

                <p>
                    Deseja acessar outra conta ou encerrar sua sessão atual?
                </p>

                <div class="perfil-botoes">
                    <a
                        href="trocar_conta.php"
                        class="perfil-botao perfil-botao-secundario"
                    >
                        Trocar de conta
                    </a>

                    <a
                        href="logout.php"
                        class="perfil-botao perfil-botao-sair"
                    >
                        Sair da conta
                    </a>
                </div>
            </section>

        </div>

        <aside>

            <div class="perfil-painel">

                <h2>Detalhes da conta</h2>

                <p class="perfil-painel-descricao">
                    Informações do seu cadastro.
                </p>

                <ul class="perfil-detalhes">
                    <li>
                        <span>Status</span>
                        <strong>Conectado</strong>
                    </li>

                    <li>
                        <span>Membro desde</span>
                        <strong><?= escaparPerfil($dataCadastro) ?></strong>
                    </li>

                    <li>
                        <span>Filmes favoritos</span>
                        <strong><?= $totalFavoritos ?></strong>
                    </li>
                </ul>

            </div>


        </aside>

    </section>

</main>

<footer class="rodape">
    <p>&copy; 2026 AfterDark. O cinema além do medo.</p>
</footer>

</body>
</html>