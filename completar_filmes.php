
<?php

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/tmdb.php";

set_time_limit(120);

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES
|--------------------------------------------------------------------------
*/

$lote = 10;
$inicio = max(0, (int) ($_GET["inicio"] ?? 0));

/*
|--------------------------------------------------------------------------
| NORMALIZAÇÃO DE TÍTULOS
|--------------------------------------------------------------------------
*/

function tituloNormalizado($titulo)
{
    $titulo = mb_strtolower(trim((string) $titulo), "UTF-8");

    return preg_replace('/[^\p{L}\p{N}]+/u', '', $titulo);
}

/*
|--------------------------------------------------------------------------
| BUSCA SEGURA DE FILMES NA TMDB
|--------------------------------------------------------------------------
*/

function localizarFilmeTMDB($titulo, $ano)
{
    $tituloOriginal = tituloNormalizado($titulo);

    if ($tituloOriginal === "") {
        return null;
    }

    $idiomas = ["pt-BR", "en-US"];

    foreach ($idiomas as $idioma) {
        $resultados = buscarFilmePorTituloTMDB($titulo, $idioma);

        foreach ($resultados as $item) {
            $tituloTMDB = tituloNormalizado($item["title"] ?? "");

            $tituloOriginalTMDB = tituloNormalizado(
                $item["original_title"] ?? ""
            );

            $anoTMDB = !empty($item["release_date"])
                ? substr($item["release_date"], 0, 4)
                : "";

            $tituloConfere = (
                $tituloOriginal === $tituloTMDB ||
                $tituloOriginal === $tituloOriginalTMDB
            );

            $anoConfere = (
                empty($ano) ||
                $anoTMDB === (string) $ano
            );

            if ($tituloConfere && $anoConfere) {
                return $item;
            }
        }
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| BUSCA DOS FILMES COM DADOS INCOMPLETOS
|--------------------------------------------------------------------------
*/

$sqlPendentes = "
    SELECT
        id,
        titulo,
        tmdb_id,
        sinopse,
        poster_url,
        ano_lancamento,
        duracao_minutos
    FROM filmes
    WHERE sinopse IS NULL OR TRIM(sinopse) = ''
       OR poster_url IS NULL OR TRIM(poster_url) = ''
       OR ano_lancamento IS NULL OR ano_lancamento = 0
       OR duracao_minutos IS NULL OR duracao_minutos = 0
    ORDER BY id
";

$pendentes = $pdo->query($sqlPendentes)->fetchAll(PDO::FETCH_ASSOC);

$total = count($pendentes);

$loteAtual = array_slice($pendentes, $inicio, $lote);

$relatorio = [];

/*
|--------------------------------------------------------------------------
| PROCESSAMENTO DO LOTE
|--------------------------------------------------------------------------
*/

foreach ($loteAtual as $local) {
    $filmeTMDB = null;

    /*
     * Primeiro, tenta consultar o ID já cadastrado.
     */
    if (!empty($local["tmdb_id"])) {
        $detalhes = buscarDetalhesFilme($local["tmdb_id"]);

        if (!empty($detalhes["id"])) {
            $filmeTMDB = $detalhes;
        }
    }

    /*
     * Se não conseguiu consultar pelo ID, pesquisa pelo título e ano.
     */
    if (!$filmeTMDB) {
        $resultado = localizarFilmeTMDB(
            $local["titulo"],
            $local["ano_lancamento"]
        );

        if (!empty($resultado["id"])) {
            $detalhes = buscarDetalhesFilme($resultado["id"]);

            if (!empty($detalhes["id"])) {
                $filmeTMDB = $detalhes;
            }
        }
    }

    /*
     * Se não encontrou correspondência confiável, não altera o registro.
     */
    if (empty($filmeTMDB["id"])) {
        $relatorio[] = [
            "titulo" => $local["titulo"],
            "status" => "Não encontrado",
            "detalhes" => "Nenhuma correspondência segura foi localizada."
        ];

        continue;
    }

    /*
     * Extrai os dados retornados pela TMDB.
     */
    $sinopse = trim($filmeTMDB["overview"] ?? "");

    $poster = !empty($filmeTMDB["poster_path"])
        ? imagemTMDB($filmeTMDB["poster_path"])
        : "";

    $ano = !empty($filmeTMDB["release_date"])
        ? (int) substr($filmeTMDB["release_date"], 0, 4)
        : null;

    $duracao = !empty($filmeTMDB["runtime"])
        ? (int) $filmeTMDB["runtime"]
        : null;

    /*
     * Atualiza somente campos vazios.
     * Os valores existentes são preservados.
     */
    $sqlAtualizar = "
        UPDATE filmes
        SET
            sinopse = CASE
                WHEN sinopse IS NULL OR TRIM(sinopse) = ''
                THEN :sinopse
                ELSE sinopse
            END,

            poster_url = CASE
                WHEN poster_url IS NULL OR TRIM(poster_url) = ''
                THEN :poster
                ELSE poster_url
            END,

            ano_lancamento = CASE
                WHEN ano_lancamento IS NULL OR ano_lancamento = 0
                THEN :ano
                ELSE ano_lancamento
            END,

            duracao_minutos = CASE
                WHEN duracao_minutos IS NULL OR duracao_minutos = 0
                THEN :duracao
                ELSE duracao_minutos
            END,

            tmdb_id = COALESCE(tmdb_id, :tmdb_id)

        WHERE id = :id
    ";

    $atualizar = $pdo->prepare($sqlAtualizar);

    $atualizar->execute([
        "sinopse" => $sinopse !== "" ? $sinopse : null,
        "poster" => $poster !== "" ? $poster : null,
        "ano" => $ano,
        "duracao" => $duracao,
        "tmdb_id" => (int) $filmeTMDB["id"],
        "id" => (int) $local["id"]
    ]);

    /*
     * Informa exatamente quais dados foram encontrados.
     */
    $camposEncontrados = [];

    if ($sinopse !== "") {
        $camposEncontrados[] = "sinopse";
    }

    if ($poster !== "") {
        $camposEncontrados[] = "pôster";
    }

    if ($ano !== null) {
        $camposEncontrados[] = "ano";
    }

    if ($duracao !== null) {
        $camposEncontrados[] = "duração";
    }

    $relatorio[] = [
        "titulo" => $local["titulo"],
        "status" => "Consultado",
        "detalhes" => count($camposEncontrados) > 0
            ? "Dados disponíveis: " . implode(", ", $camposEncontrados) . "."
            : "Filme localizado, mas a TMDB não retornou os dados necessários."
    ];
}

/*
|--------------------------------------------------------------------------
| PROGRESSO
|--------------------------------------------------------------------------
*/

$proximo = $inicio + count($loteAtual);

$quantidadeProcessada = min($proximo, $total);

$temProximoLote = $proximo < $total && count($loteAtual) > 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Completar filmes | AfterDark</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .resultado-atualizacao {
            max-width: 1000px;
            margin: 40px auto;
            padding: 28px;
            color: #f5f5f5;
            background: #101010;
            border: 1px solid #352c2f;
            border-radius: 14px;
        }

        .resultado-atualizacao h1,
        .resultado-atualizacao h2 {
            color: #c34a5a;
        }

        .progresso-atualizacao {
            width: 100%;
            height: 12px;
            accent-color: #8b1e2d;
        }

        .lista-resultados {
            padding-left: 22px;
        }

        .lista-resultados li {
            margin: 14px 0;
            line-height: 1.6;
        }

        .status-consultado {
            color: #b8e6c1;
        }

        .status-nao-encontrado {
            color: #ffb3b3;
        }

        .acoes-atualizacao {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .acoes-atualizacao a {
            display: inline-block;
            padding: 12px 18px;
            color: #fff;
            background: #8b1e2d;
            border-radius: 8px;
            text-decoration: none;
        }

        .acoes-atualizacao a:hover {
            background: #a92c3d;
        }

        .aviso-atualizacao {
            color: #c8c8c8;
            line-height: 1.6;
        }
    </style>
</head>

<body>

<main class="resultado-atualizacao">

    <h1>Atualização do catálogo</h1>

    <p class="aviso-atualizacao">
        AfterDark · Consulta de dados na TMDB
    </p>

    <p>
        <strong>Progresso:</strong>
        <?= $quantidadeProcessada ?> de <?= $total ?> filmes pendentes
    </p>

    <progress
        class="progresso-atualizacao"
        value="<?= $quantidadeProcessada ?>"
        max="<?= max(1, $total) ?>"
    ></progress>

    <h2>Resultado deste lote</h2>

    <?php if (empty($relatorio)): ?>

        <p>Não há filmes neste lote para processar.</p>

    <?php else: ?>

        <ul class="lista-resultados">

            <?php foreach ($relatorio as $linha): ?>

                <li>
                    <strong>
                        <?= htmlspecialchars(
                            $linha["titulo"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                    <?php if ($linha["status"] === "Consultado"): ?>

                        <span class="status-consultado">
                            — <?= htmlspecialchars(
                                $linha["status"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    <?php else: ?>

                        <span class="status-nao-encontrado">
                            — <?= htmlspecialchars(
                                $linha["status"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    <?php endif; ?>

                    <br>

                    <?= htmlspecialchars(
                        $linha["detalhes"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>

    <div class="acoes-atualizacao">

        <?php if ($temProximoLote): ?>

            <a href="completar_filmes.php?inicio=<?= $proximo ?>">
                Processar próximo lote
            </a>

        <?php endif; ?>

        <a href="completar_filmes.php">
            Recomeçar verificação
        </a>

        <a href="index.php">
            Voltar ao AfterDark
        </a>

    </div>

    <?php if (!$temProximoLote): ?>

        <p class="aviso-atualizacao">
            Verificação dos filmes pendentes concluída.
            Alguns campos podem continuar vazios se a TMDB não fornecer
            os dados ou se não houver uma correspondência segura.
        </p>

    <?php endif; ?>

</main>

</body>
</html>
