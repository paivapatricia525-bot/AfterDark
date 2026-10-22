
<?php
session_start();

require_once "conexao.php";
require_once "tmdb.php";

// Recebe o ID local do filme no MySQL.
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: catalogo.php");
    exit;
}

// Busca o filme no banco de dados.
$sql = "
    SELECT
        f.id,
        f.tmdb_id,
        f.titulo,
        f.sinopse,
        f.poster_url,
        f.ano_lancamento,
        f.duracao_minutos,
        f.classificacao,
        GROUP_CONCAT(
            DISTINCT c.nome
            ORDER BY c.nome
            SEPARATOR ', '
        ) AS categorias
    FROM filmes f
    LEFT JOIN filmes_categorias fc
        ON fc.filme_id = f.id
    LEFT JOIN categorias c
        ON c.id = fc.categoria_id
    WHERE f.id = :id
    GROUP BY
        f.id,
        f.tmdb_id,
        f.titulo,
        f.sinopse,
        f.poster_url,
        f.ano_lancamento,
        f.duracao_minutos,
        f.classificacao
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $id]);

$filme = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o filme não existir, retorna status HTTP 404.
if (!$filme) {
    http_response_code(404);
}

// Inicializa os dados com as informações do MySQL.
$titulo = $filme["titulo"] ?? "";
$sinopse = $filme["sinopse"] ?? "";
$poster = $filme["poster_url"] ?? "";
$ano = $filme["ano_lancamento"] ?? "";
$duracao = $filme["duracao_minutos"] ?? "";
$classificacao = $filme["classificacao"] ?? "";
$categorias = $filme["categorias"] ?? "";
$avaliacao = null;
$elenco = [];
$trailer = null;

// Busca informações adicionais na TMDB, quando disponíveis.
if ($filme && !empty($filme["tmdb_id"])) {

    $dadosTMDB = buscarDetalhesFilme(
        (int) $filme["tmdb_id"]
    );

    if (!empty($dadosTMDB["id"])) {

        $titulo = $dadosTMDB["title"] ?? $titulo;
        $sinopse = $dadosTMDB["overview"] ?? $sinopse;

        if (!empty($dadosTMDB["poster_path"])) {
            $poster = imagemTMDB(
                $dadosTMDB["poster_path"]
            );
        }

        if (!empty($dadosTMDB["release_date"])) {
            $ano = substr(
                $dadosTMDB["release_date"],
                0,
                4
            );
        }

        if (
            isset($dadosTMDB["runtime"]) &&
            (int) $dadosTMDB["runtime"] > 0
        ) {
            $duracao = (int) $dadosTMDB["runtime"];
        }

        if (isset($dadosTMDB["vote_average"])) {
            $avaliacao = number_format(
                (float) $dadosTMDB["vote_average"],
                1,
                ",",
                ""
            );
        }

        // Obtém os gêneros informados pela TMDB.
        if (!empty($dadosTMDB["genres"])) {
            $nomesGeneros = array_column(
                $dadosTMDB["genres"],
                "name"
            );

            $categorias = implode(", ", $nomesGeneros);
        }

        // Seleciona até cinco integrantes do elenco.
        if (!empty($dadosTMDB["credits"]["cast"])) {
            $elenco = array_slice(
                $dadosTMDB["credits"]["cast"],
                0,
                5
            );
        }

        // Procura um trailer do YouTube.
        if (!empty($dadosTMDB["videos"]["results"])) {

            $videos = $dadosTMDB["videos"]["results"];

            // Prioriza trailers oficiais em português.
            foreach ($videos as $video) {
                if (
                    ($video["site"] ?? "") === "YouTube" &&
                    ($video["type"] ?? "") === "Trailer" &&
                    !empty($video["key"]) &&
                    in_array(
                        $video["iso_639_1"] ?? "",
                        ["pt", "en"],
                        true
                    ) &&
                    ($video["official"] ?? false) === true
                ) {
                    $trailer = $video["key"];

                    if (($video["iso_639_1"] ?? "") === "pt") {
                        break;
                    }
                }
            }

            // Se não houver trailer oficial, aceita outro trailer.
            if (!$trailer) {
                foreach ($videos as $video) {
                    if (
                        ($video["site"] ?? "") === "YouTube" &&
                        ($video["type"] ?? "") === "Trailer" &&
                        !empty($video["key"])
                    ) {
                        $trailer = $video["key"];
                        break;
                    }
                }
            }

            // Se não houver trailer, tenta encontrar um teaser.
            if (!$trailer) {
                foreach ($videos as $video) {
                    if (
                        ($video["site"] ?? "") === "YouTube" &&
                        ($video["type"] ?? "") === "Teaser" &&
                        !empty($video["key"])
                    ) {
                        $trailer = $video["key"];
                        break;
                    }
                }
            }
        }
    }
}

// Verifica se o usuário já favoritou este filme.
$jaFavoritado = false;

if ($filme && isset($_SESSION["usuario_id"])) {

    $consultaFavorito = $pdo->prepare("
        SELECT 1
        FROM favoritos
        WHERE usuario_id = :usuario_id
          AND filme_id = :filme_id
        LIMIT 1
    ");

    $consultaFavorito->execute([
        "usuario_id" => $_SESSION["usuario_id"],
        "filme_id" => $filme["id"]
    ]);

    $jaFavoritado = (bool) $consultaFavorito->fetchColumn();
}

// Protege os valores exibidos no HTML.
function escaparDetalhes($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $filme
            ? escaparDetalhes($titulo) . " | AfterDark"
            : "Filme não encontrado | AfterDark" ?>
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="cabecalho">

    <a href="index.php" class="logo">
        <img
            src="assets/img/logo-afterdark.png"
            alt="Logo AfterDark"
        >
    </a>

    <nav class="menu">

        <a href="index.php">Início</a>
        <a href="catalogo.php">Filmes</a>

        <?php if (isset($_SESSION["usuario_id"])): ?>

            <a href="favoritos.php">Minha lista</a>
            <a href="logout.php">Sair</a>

        <?php else: ?>

            <a href="login.php" class="botao-login">Entrar</a>
            <a href="cadastro.php">Criar conta</a>

        <?php endif; ?>

    </nav>

</header>

<main class="pagina-catalogo">

    <?php if ($filme): ?>

        <section class="detalhes-filme">

            <p class="subtitulo">
                ENTRE NA ESCURIDÃO
            </p>

            <h1>
                <?= escaparDetalhes($titulo) ?>
            </h1>

            <img
                src="<?= escaparDetalhes(
                    $poster ?: "assets/img/poster-padrao.png"
                ) ?>"
                alt="Pôster de <?= escaparDetalhes($titulo) ?>"
                style="max-width: 280px; height: auto;"
            >

            <p>
                <?= escaparDetalhes(
                    $sinopse ?: "Sinopse não disponível."
                ) ?>
            </p>

            <p>
                <strong>Gêneros:</strong>
                <?= escaparDetalhes(
                    $categorias ?: "Não informado"
                ) ?>
            </p>

            <?php if (!empty($ano)): ?>

                <p>
                    <strong>Ano de lançamento:</strong>
                    <?= escaparDetalhes($ano) ?>
                </p>

            <?php endif; ?>

            <?php if (!empty($duracao)): ?>

                <p>
                    <strong>Duração:</strong>
                    <?= (int) $duracao ?> minutos
                </p>

            <?php endif; ?>

            <?php if ($avaliacao !== null): ?>

                <p>
                    <strong>Avaliação TMDB:</strong>
                    <?= escaparDetalhes($avaliacao) ?>/10
                </p>

            <?php endif; ?>

            <?php if (!empty($classificacao)): ?>

                <p>
                    <strong>Classificação:</strong>
                    <?= escaparDetalhes($classificacao) ?>
                </p>

            <?php endif; ?>

            <!-- Trailer ou prévia do filme -->
            <section class="previa-filme">

                <h2>Assista à prévia</h2>

                <?php if ($trailer): ?>

                    <div class="player-trailer">

                        <iframe
                            src="https://www.youtube-nocookie.com/embed/<?= escaparDetalhes($trailer) ?>"
                            title="Trailer de <?= escaparDetalhes($titulo) ?>"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>

                    </div>

                <?php else: ?>

                    <p>
                        Não encontramos um trailer disponível
                        para este filme.
                    </p>

                <?php endif; ?>

            </section>

            <!-- Elenco -->
            <?php if (!empty($elenco)): ?>

                <section class="elenco-filme">

                    <h2>Elenco</h2>

                    <ul>
                        <?php foreach ($elenco as $pessoa): ?>

                            <li>
                                <?= escaparDetalhes(
                                    $pessoa["name"] ?? "Nome indisponível"
                                ) ?>

                                <?php if (!empty($pessoa["character"])): ?>

                                    — <?= escaparDetalhes(
                                        $pessoa["character"]
                                    ) ?>

                                <?php endif; ?>
                            </li>

                        <?php endforeach; ?>
                    </ul>

                </section>

            <?php endif; ?>

            <!-- Ações do usuário -->
            <div class="acoes-detalhes">

                <?php if (isset($_SESSION["usuario_id"])): ?>

                    <form action="favoritar.php" method="POST">

                        <input
                            type="hidden"
                            name="filme_id"
                            value="<?= (int) $filme["id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="acao"
                            value="<?= $jaFavoritado
                                ? "remover"
                                : "adicionar" ?>"
                        >

                        <input
                            type="hidden"
                            name="origem"
                            value="detalhes"
                        >

                        <button
                            type="submit"
                            class="botao-principal"
                        >
                            <?= $jaFavoritado
                                ? "♥ Remover dos favoritos"
                                : "♡ Adicionar aos favoritos" ?>
                        </button>

                    </form>

                <?php else: ?>

                    <p>
                        Entre na sua conta para salvar
                        este filme nos favoritos.
                    </p>

                    <a
                        href="login.php"
                        class="botao-principal"
                    >
                        Entrar para favoritar
                    </a>

                <?php endif; ?>

                <a
                    href="catalogo.php"
                    class="botao-login"
                >
                    Voltar ao catálogo
                </a>

            </div>

        </section>

    <?php else: ?>

        <section class="cabecalho-catalogo">

            <h1>Filme não encontrado</h1>

            <p>
                Não encontramos esse filme no nosso catálogo.
            </p>

            <a href="catalogo.php" class="botao-login">
                Voltar ao catálogo
            </a>

        </section>

    <?php endif; ?>

</main>

<footer class="rodape">

    <p>
        &copy; <?= date("Y") ?> AfterDark.
        O cinema além do medo.
    </p>

</footer>

</body>
</html>
