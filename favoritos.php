
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "conexao.php";

function escaparFavoritos($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$sql = "
    SELECT
        f.id,
        f.titulo,
        f.sinopse,
        f.poster_url,
        f.ano_lancamento,
        fav.criado_em
    FROM favoritos AS fav
    INNER JOIN filmes AS f ON f.id = fav.filme_id
    WHERE fav.usuario_id = :usuario_id
    ORDER BY fav.criado_em DESC
";

$consulta = $pdo->prepare($sql);
$consulta->execute([
    "usuario_id" => $_SESSION["usuario_id"]
]);

$filmes = $consulta->fetchAll(PDO::FETCH_ASSOC);
$totalFilmes = count($filmes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Minha Lista | AfterDark</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/favoritos.css">
</head>

<body class="pagina-minha-lista">

<header class="cabecalho">
    <a href="index.php" class="logo">
        <img src="assets/img/logo-afterdark.png" alt="AfterDark">
    </a>

    <nav class="menu">
        <a href="index.php">Início</a>
        <a href="catalogo.php">Filmes</a>
        <a href="favoritos.php" class="ativo">Minha lista</a>
        <a href="perfil.php">Meu perfil</a>
    </nav>
</header>

<main class="minha-lista">

    <section class="lista-hero">
        <div class="lista-hero-conteudo">
            <span class="lista-etiqueta">
                <span class="lista-ponto"></span>
                SUA COLEÇÃO PARTICULAR
            </span>

            <h1>Minha <span>Lista.</span></h1>

            <p>
                Seus pesadelos favoritos, reunidos em um só lugar.
                Escolha seu próximo filme... se tiver coragem.
            </p>

            <div class="lista-resumo">
                <div class="lista-resumo-icone">▤</div>

                <div>
                    <strong><?= $totalFilmes ?></strong>
                    <span>
                        <?= $totalFilmes === 1
                            ? "filme salvo"
                            : "filmes salvos" ?>
                    </span>
                </div>
            </div>
        </div>

        
    <section class="lista-conteudo">

        <div class="lista-secao-cabecalho">
            <div>
                <span class="lista-subtitulo">ARQUIVO PESSOAL</span>
                <h2>Seus filmes <span>salvos</span></h2>
            </div>

            <a href="catalogo.php" class="lista-explorar">
                Explorar catálogo <span>↗</span>
            </a>
        </div>

        <?php if (empty($filmes)): ?>

            <div class="lista-vazia">
                <div class="lista-vazia-icone">✦</div>

                <span class="lista-subtitulo">NENHUM REGISTRO ENCONTRADO</span>

                <h2>O silêncio tomou conta.</h2>

                <p>
                    Sua coleção ainda está vazia. Explore o catálogo
                    e salve os filmes que você não quer esquecer.
                </p>

                <a href="catalogo.php" class="lista-botao-principal">
                    <span>＋</span> Descobrir filmes
                </a>
            </div>

        <?php else: ?>

            <div class="lista-grade">

                <?php foreach ($filmes as $indice => $filme): ?>

                    <?php
                    $poster = !empty($filme["poster_url"])
                        ? $filme["poster_url"]
                        : "assets/img/poster-padrao.png";

                    $sinopse = trim((string) ($filme["sinopse"] ?? ""));
                    ?>

                    <article class="lista-card">

                        <a
                            href="detalhes.php?id=<?= (int) $filme["id"] ?>"
                            class="lista-card-poster"
                            aria-label="Ver detalhes de <?= escaparFavoritos($filme["titulo"]) ?>"
                        >
                            <img
                                src="<?= escaparFavoritos($poster) ?>"
                                alt="Pôster de <?= escaparFavoritos($filme["titulo"]) ?>"
                                loading="lazy"
                            >

                            <span class="lista-card-numero">
                                <?= str_pad((string) ($indice + 1), 2, "0", STR_PAD_LEFT) ?>
                            </span>

                            <span class="lista-card-sombra"></span>

                            <span class="lista-card-acao">
                                <span>▶</span>
                                Ver detalhes
                            </span>
                        </a>

                        <div class="lista-card-info">
                            <div class="lista-card-meta">
                                <span>
                                    <?= escaparFavoritos(
                                        $filme["ano_lancamento"] ?: "Ano desconhecido"
                                    ) ?>
                                </span>
                                <span class="lista-card-ponto">•</span>
                                <span>AFTERDARK</span>
                            </div>

                            <h3>
                                <?= escaparFavoritos($filme["titulo"]) ?>
                            </h3>

                            <p class="lista-card-sinopse">
                                <?= escaparFavoritos(
                                    $sinopse !== ""
                                        ? $sinopse
                                        : "Nenhuma sinopse disponível para este filme."
                                ) ?>
                            </p>

                            <a
                                href="detalhes.php?id=<?= (int) $filme["id"] ?>"
                                class="lista-card-link"
                            >
                                Explorar filme <span>→</span>
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="rodape">
    <p>© <?= date("Y") ?> AfterDark — O medo nunca termina.</p>
</footer>

</body>
</html>
