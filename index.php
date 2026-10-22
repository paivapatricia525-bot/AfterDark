<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$usuarioLogado = isset($_SESSION["usuario_id"]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AfterDark | O lado sombrio do cinema</title>

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
            <a href="favoritos.php">Minha lista</a>

            <?php if ($usuarioLogado): ?>
                <a href="perfil.php">Meu perfil</a>
                <a href="logout.php" class="botao-login">Sair</a>
            <?php else: ?>
                <a href="login.php" class="botao-login">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="destaque">
            <div class="conteudo-destaque">

                <p class="subtitulo">
                    NEM TODO PESADELO TERMINA AO ACORDAR
                </p>

                <h1>Algumas histórias<br>nunca deveriam<br>ser assistidas.</h1>

                <p class="descricao">
                    Entre no universo do horror. Explore obsessões, presenças
                    inexplicáveis e segredos que desafiam a sanidade. Aqui, o medo
                    tem muitas formas — e nenhuma delas vai embora tão fácil.
                </p>

                <a href="catalogo.php" class="botao-principal">
                    ENFRENTE O DESCONHECIDO
                </a>

            </div>
        </section>

        <section class="apresentacao">

            <p class="subtitulo">
                POR TRÁS DE CADA HISTÓRIA, UM NOVO PESADELO
            </p>

            <h2>O medo não é o fim da história.</h2>

            <p>
                Do terror psicológico ao horror sobrenatural, cada filme abre
                uma porta para o desconhecido. Descubra histórias que perturbam,
                desafiam suas certezas e continuam com você muito depois dos créditos.
            </p>

        </section>
    </main>

    <footer class="rodape">
        <p>&copy; 2026 AfterDark. O cinema além do medo.</p>
    </footer>

    <script src="assets/js/script.js"></script>

</body>
</html>