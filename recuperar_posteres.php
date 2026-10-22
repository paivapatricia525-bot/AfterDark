
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/tmdb.php";

header("Content-Type: text/html; charset=UTF-8");

function escaparRecuperacao($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function normalizarTituloRecuperacao($titulo)
{
    $titulo = trim((string) $titulo);

    if (function_exists("mb_strtolower")) {
        $titulo = mb_strtolower($titulo, "UTF-8");
    } else {
        $titulo = strtolower($titulo);
    }

    if (function_exists("iconv")) {
        $normalizado = @iconv(
            "UTF-8",
            "ASCII//TRANSLIT//IGNORE",
            $titulo
        );

        if ($normalizado !== false) {
            $titulo = $normalizado;
        }
    }

    $titulo = preg_replace("/[^a-z0-9]+/", " ", $titulo);

    return trim(preg_replace("/\s+/", " ", $titulo));
}

/**
 * Extrai o ano de uma data no formato AAAA-MM-DD.
 */
function anoRecuperacao($data)
{
    if (
        is_string($data) &&
        preg_match('/^(\d{4})-\d{2}-\d{2}$/', $data, $matches)
    ) {
        return (int) $matches[1];
    }

    return null;
}

/**
 * Busca em português e inglês e remove resultados duplicados.
 */
function pesquisarFilmeRecuperacao($titulo)
{
    $resultadosUnicos = [];

    foreach (["pt-BR", "en-US"] as $idioma) {
        $resultados = buscarFilmePorTituloTMDB($titulo, $idioma);

        if (!is_array($resultados)) {
            continue;
        }

        foreach ($resultados as $resultado) {
            if (!is_array($resultado) || empty($resultado["id"])) {
                continue;
            }

            $id = (int) $resultado["id"];

            if (!isset($resultadosUnicos[$id])) {
                $resultadosUnicos[$id] = $resultado;
            }
        }
    }

    return array_values($resultadosUnicos);
}

/**
 * Escolhe apenas um resultado com título correspondente.
 * Se houver ano cadastrado, prioriza o mesmo ano.
 */
function escolherFilmeRecuperacao($tituloLocal, $anoLocal, $resultados)
{
    $tituloEsperado = normalizarTituloRecuperacao($tituloLocal);
    $candidatos = [];

    foreach ($resultados as $resultado) {
        $tituloTMDB = normalizarTituloRecuperacao(
            $resultado["title"] ?? ""
        );

        $originalTMDB = normalizarTituloRecuperacao(
            $resultado["original_title"] ?? ""
        );

        if ($tituloEsperado === "") {
            continue;
        }

        $tituloIgual = (
            $tituloTMDB === $tituloEsperado ||
            $originalTMDB === $tituloEsperado
        );

        if (!$tituloIgual) {
            continue;
        }

        $posterPath = (string) ($resultado["poster_path"] ?? "");

        if (!preg_match('#^/[A-Za-z0-9._-]+$#', $posterPath)) {
            continue;
        }

        $anoTMDB = anoRecuperacao(
            $resultado["release_date"] ?? ""
        );

        $pontuacao = 10;

        if (
            $anoLocal !== null &&
            $anoTMDB !== null
        ) {
            if ($anoLocal === $anoTMDB) {
                $pontuacao += 20;
            } else {
                $pontuacao -= min(abs($anoLocal - $anoTMDB), 20);
            }
        }

        $resultado["_pontuacao_recuperacao"] = $pontuacao;
        $candidatos[] = $resultado;
    }

    if (empty($candidatos)) {
        return null;
    }

    usort($candidatos, function ($a, $b) {
        return $b["_pontuacao_recuperacao"]
            <=> $a["_pontuacao_recuperacao"];
    });

    /*
     * Se há ano local e o resultado tem ano diferente,
     * não aceita automaticamente o candidato.
     */
    $melhor = $candidatos[0];
    $anoTMDB = anoRecuperacao($melhor["release_date"] ?? "");

    if (
        $anoLocal !== null &&
        $anoTMDB !== null &&
        $anoLocal !== $anoTMDB
    ) {
        return null;
    }

    return $melhor;
}

if (
    !function_exists("buscarFilmePorTituloTMDB") ||
    !function_exists("imagemTMDB")
) {
    http_response_code(500);
    exit("As funções necessárias não foram encontradas em tmdb.php.");
}

/*
 * Exige confirmação por POST.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recuperar pôsteres | AfterDark</title>
        <style>
            body {
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: #101010;
                color: #f5f5f5;
                font-family: Arial, sans-serif;
                line-height: 1.6;
            }
            h1 { color: #c64255; }
            button, a {
                display: inline-block;
                padding: 12px 18px;
                border: 0;
                border-radius: 6px;
                background: #8b1e2d;
                color: white;
                text-decoration: none;
                cursor: pointer;
            }
            .aviso {
                padding: 14px;
                border: 1px solid #8b1e2d;
                border-radius: 6px;
                background: #241216;
            }
        </style>
    </head>
    <body>
        <h1>Recuperação de pôsteres</h1>
        <p>O processo buscará pôsteres ausentes na TMDB.</p>
        <p class="aviso">
            Faça backup do banco antes de continuar.
            Somente pôsteres ausentes serão preenchidos.
        </p>
        <form method="POST">
            <button type="submit">Iniciar recuperação</button>
        </form>
        <p><a href="catalogo.php">Voltar ao catálogo</a></p>
    </body>
    </html>
    <?php
    exit;
}

try {
    $consulta = $pdo->query(
        "SELECT id, titulo, ano_lancamento, tmdb_id, poster_url
         FROM filmes
         WHERE poster_url IS NULL
            OR TRIM(poster_url) = ''
         ORDER BY id ASC"
    );

    $pendentes = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $erro) {
    error_log("Erro ao consultar filmes: " . $erro->getMessage());
    http_response_code(500);
    exit("Não foi possível consultar os filmes no banco.");
}

$atualizados = 0;
$semCorrespondencia = 0;
$ignorados = 0;
$erros = 0;

echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Resultado da recuperação | AfterDark</title>";
echo "<style>
    body { max-width: 1000px; margin: 30px auto; padding: 20px;
        background: #101010; color: #f5f5f5; font-family: Arial, sans-serif;
        line-height: 1.6; }
    h1, h2 { color: #c64255; }
    li { margin: 8px 0; }
    .sucesso { color: #83c99a; }
    .aviso { color: #ffd27d; }
    .erro { color: #ff8a8a; }
    a { display: inline-block; margin: 10px 10px 0 0; padding: 10px 15px;
        border-radius: 5px; background: #8b1e2d; color: white;
        text-decoration: none; }
</style></head><body>";

echo "<h1>Recuperação de pôsteres — AfterDark</h1>";
echo "<p>Filmes sem pôster a verificar: " . count($pendentes) . "</p><ul>";

foreach ($pendentes as $filmeLocal) {
    $idLocal = (int) $filmeLocal["id"];
    $tituloLocal = trim((string) $filmeLocal["titulo"]);
    $anoLocal = !empty($filmeLocal["ano_lancamento"])
        ? (int) substr((string) $filmeLocal["ano_lancamento"], 0, 4)
        : null;

    try {
        $resultados = pesquisarFilmeRecuperacao($tituloLocal);

        $correspondencia = escolherFilmeRecuperacao(
            $tituloLocal,
            $anoLocal,
            $resultados
        );

        if (!$correspondencia) {
            $semCorrespondencia++;
            echo '<li class="aviso">Sem correspondência segura: '
                . escaparRecuperacao($tituloLocal) . "</li>";
            continue;
        }

        $tmdbId = (int) $correspondencia["id"];
        $posterPath = (string) $correspondencia["poster_path"];
        $poster = imagemTMDB($posterPath);

        $host = strtolower((string) parse_url($poster, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($poster, PHP_URL_SCHEME));

        if (
            $tmdbId <= 0 ||
            !filter_var($poster, FILTER_VALIDATE_URL) ||
            !in_array($scheme, ["http", "https"], true) ||
            $host !== "image.tmdb.org"
        ) {
            $semCorrespondencia++;
            echo '<li class="aviso">URL de pôster inválida: '
                . escaparRecuperacao($tituloLocal) . "</li>";
            continue;
        }

        $pdo->beginTransaction();

        $verificar = $pdo->prepare(
            "SELECT id FROM filmes WHERE tmdb_id = :tmdb_id LIMIT 1"
        );
        $verificar->execute(["tmdb_id" => $tmdbId]);
        $idExistente = $verificar->fetchColumn();

        if (
            $idExistente !== false &&
            (int) $idExistente !== $idLocal
        ) {
            $pdo->rollBack();
            $ignorados++;
            echo '<li class="aviso">Ignorado para evitar duplicação: '
                . escaparRecuperacao($tituloLocal) . "</li>";
            continue;
        }

        /*
         * Atualiza apenas registros sem pôster.
         * Nunca substitui um tmdb_id que já existe.
         */
        $atualizar = $pdo->prepare(
            "UPDATE filmes
             SET tmdb_id = COALESCE(tmdb_id, :tmdb_id),
                 poster_url = :poster_url
             WHERE id = :id
               AND (poster_url IS NULL OR TRIM(poster_url) = '')"
        );

        $atualizar->execute([
            "tmdb_id" => $tmdbId,
            "poster_url" => $poster,
            "id" => $idLocal
        ]);

        $alteracoes = $atualizar->rowCount();
        $pdo->commit();

        if ($alteracoes > 0) {
            $atualizados++;
            echo '<li class="sucesso">Pôster recuperado: '
                . escaparRecuperacao($tituloLocal) . "</li>";
        } else {
            $ignorados++;
            echo '<li class="aviso">Registro já atualizado: '
                . escaparRecuperacao($tituloLocal) . "</li>";
        }
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $erros++;
        error_log(
            "Erro ao recuperar pôster do filme ID {$idLocal}: "
            . $erro->getMessage()
        );

        echo '<li class="erro">Erro ao processar: '
            . escaparRecuperacao($tituloLocal) . "</li>";
    }
}

echo "</ul>";
echo "<h2>Resultado</h2>";
echo "<p>Filmes atualizados: " . $atualizados . "</p>";
echo "<p>Sem correspondência segura: " . $semCorrespondencia . "</p>";
echo "<p>Ignorados: " . $ignorados . "</p>";
echo "<p>Erros: " . $erros . "</p>";
echo '<p><a href="catalogo.php">Voltar ao catálogo</a></p>';
echo '<p class="aviso">Após conferir o resultado, remova este arquivo '
    . 'do projeto para evitar execuções indevidas.</p>';
echo "</body></html>";
