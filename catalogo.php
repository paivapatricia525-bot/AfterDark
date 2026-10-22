
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/tmdb.php";

$usuarioLogado = isset($_SESSION["usuario_id"]);

if (!function_exists("escaparCatalogo")) {
    function escaparCatalogo($valor)
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            "UTF-8"
        );
    }
}

$filmes = [];
$categorias = [];
$erroCatalogo = false;
$erroTMDB = false;

/*
|--------------------------------------------------------------------------
| 1. Sincronização com a TMDB
|--------------------------------------------------------------------------
| Busca até três páginas de filmes de terror.
| Se a API falhar, os filmes já salvos continuam disponíveis.
*/

try {
    for ($pagina = 1; $pagina <= 3; $pagina++) {
        $dadosTMDB = buscarFilmesTerror($pagina);

        if (!isset($dadosTMDB["results"]) ||
            !is_array($dadosTMDB["results"]) ||
            empty($dadosTMDB["results"])) {

            if ($pagina === 1) {
                $erroTMDB = true;
            }

            break;
        }

        foreach ($dadosTMDB["results"] as $filmeTMDB) {
            try {
                sincronizarFilmeTMDB($pdo, $filmeTMDB);
            } catch (Throwable $erro) {
                error_log(
                    "Erro ao sincronizar filme no AfterDark: " .
                    $erro->getMessage()
                );
            }
        }
    }
} catch (Throwable $erro) {
    error_log(
        "Erro na comunicação com a TMDB: " .
        $erro->getMessage()
    );

    $erroTMDB = true;
}

/*
|--------------------------------------------------------------------------
| 2. Consulta os filmes salvos no banco
|--------------------------------------------------------------------------
*/

try {
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
            ) AS categorias,

            GROUP_CONCAT(
                DISTINCT c.slug
                ORDER BY c.slug
                SEPARATOR ' '
            ) AS slugs_categorias

        FROM filmes f

        LEFT JOIN filmes_categorias fc
            ON fc.filme_id = f.id

        LEFT JOIN categorias c
            ON c.id = fc.categoria_id

        GROUP BY
            f.id,
            f.tmdb_id,
            f.titulo,
            f.sinopse,
            f.poster_url,
            f.ano_lancamento,
            f.duracao_minutos,
            f.classificacao

        ORDER BY f.titulo ASC
    ";

    $stmt = $pdo->query($sql);
    $filmes = $stmt->fetchAll();

    $stmtCategorias = $pdo->query(
        "SELECT nome, slug
         FROM categorias
         ORDER BY nome ASC"
    );

    $categorias = $stmtCategorias->fetchAll();

} catch (Throwable $erro) {
    error_log(
        "Erro ao consultar catálogo AfterDark: " .
        $erro->getMessage()
    );

    $erroCatalogo = true;
}

/*
|--------------------------------------------------------------------------
| 3. Prepara as URLs dos pôsteres
|--------------------------------------------------------------------------
*/

foreach ($filmes as &$filme) {
    $poster = trim((string) ($filme["poster_url"] ?? ""));

    if ($poster !== "" &&
        filter_var($poster, FILTER_VALIDATE_URL) &&
        in_array(
            strtolower((string) parse_url($poster, PHP_URL_SCHEME)),
            ["http", "https"],
            true
        )) {

        $filme["poster_exibicao"] = $poster;

    } else {
        $filme["poster_exibicao"] = "assets/img/poster-padrao.png";
    }
}

unset($filme);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catálogo de filmes | AfterDark</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
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
        <a href="favoritos.php">Minha lista</a>

        <?php if ($usuarioLogado): ?>
            <a href="perfil.php">Meu perfil</a>
        <?php else: ?>
            <a href="login.php" class="botao-login">Entrar</a>
        <?php endif; ?>
    </nav>

</header>

<main class="pagina-catalogo">

    <div class="cabecalho-catalogo">
        <h1>Catálogo de terror</h1>

        <p>ENTRE SE TIVER CORAGEM</p>

        <p>
            Explore histórias sombrias e descubra seu próximo pesadelo.
        </p>
    </div>

    <section class="filtros-catalogo">

        <div class="campo-formulario">
            <label for="busca-filme">Pesquisar filme</label>

            <input
                type="search"
                id="busca-filme"
                placeholder="Digite o nome de um filme..."
                autocomplete="off"
            >
        </div>

        <div class="campo-formulario">
            <label for="filtro-categoria">Filtrar por categoria</label>

            <select id="filtro-categoria">
                <option value="">Todas as categorias</option>

                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= escaparCatalogo($categoria["slug"]) ?>">
                        <?= escaparCatalogo($categoria["nome"]) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

    </section>

    <?php if ($erroCatalogo): ?>

        <p class="mensagem-erro">
            Não foi possível consultar o catálogo.
            Verifique a conexão com o banco de dados.
        </p>

    <?php elseif ($erroTMDB): ?>

        <p class="mensagem-erro">
            Não foi possível atualizar os filmes pela TMDB.
            Exibindo os filmes que já estão salvos no banco.
        </p>

    <?php endif; ?>

    <section class="grade-filmes" id="lista-filmes">

        <?php if ($erroCatalogo): ?>

            <p class="mensagem-vazia">
                O catálogo está temporariamente indisponível.
                Tente novamente mais tarde.
            </p>

        <?php elseif (empty($filmes)): ?>

            <p class="mensagem-vazia">
                Nenhum filme foi encontrado no catálogo.
                Tente novamente mais tarde.
            </p>

        <?php else: ?>

            <?php foreach ($filmes as $filme): ?>

                <article
                    class="cartao-filme"
                    data-categorias="<?= escaparCatalogo($filme["slugs_categorias"] ?? "") ?>"
                >

                    <a
                        href="detalhes.php?id=<?= (int) $filme["id"] ?>"
                        class="link-poster"
                    >

                        <img
                            src="<?= escaparCatalogo($filme["poster_exibicao"]) ?>"
                            alt="Pôster de <?= escaparCatalogo($filme["titulo"]) ?>"
                            loading="lazy"
                            onerror="this.onerror=null; this.alt='Pôster indisponível';"
                        >

                    </a>

                    <h2>
                        <?= escaparCatalogo($filme["titulo"]) ?>
                    </h2>

                    <p>
                        <?= escaparCatalogo(
                            !empty($filme["sinopse"])
                                ? $filme["sinopse"]
                                : "Sinopse não disponível."
                        ) ?>
                    </p>

                    <p class="categorias-filme">
                        <?= escaparCatalogo(
                            !empty($filme["categorias"])
                                ? $filme["categorias"]
                                : "Terror"
                        ) ?>
                    </p>

                    <p class="informacoes-filme">
                        Ano:
                        <?= escaparCatalogo(
                            $filme["ano_lancamento"] ?? "Não informado"
                        ) ?>
                    </p>

                    <a
                        href="detalhes.php?id=<?= (int) $filme["id"] ?>"
                        class="botao-secundario"
                    >
                        Ver detalhes
                    </a>

                    <?php if ($usuarioLogado): ?>

                        <form action="favoritar.php" method="POST">

                            <input
                                type="hidden"
                                name="filme_id"
                                value="<?= (int) $filme["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="acao"
                                value="adicionar"
                            >

                            <input
                                type="hidden"
                                name="origem"
                                value="catalogo"
                            >

                            <button
                                type="submit"
                                class="botao-principal"
                            >
                                ♡ Adicionar aos favoritos
                            </button>

                        </form>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="botao-principal"
                        >
                            Entre para favoritar
                        </a>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

        <p id="mensagem-sem-resultados" hidden>
            Nenhum filme encontrado com esses filtros.
        </p>

    </section>

</main>

<footer class="rodape">
    <p>
        © <?= date("Y") ?> AfterDark — O medo nunca termina.
    </p>
</footer>

</body>
</html>
