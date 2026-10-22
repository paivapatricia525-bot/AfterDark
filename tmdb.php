
<?php

require_once __DIR__ . "/config.php";

/**
 * Consulta a API da TMDB.
 */
function consultarTMDB($endpoint, $parametros = [])
{
    $url = rtrim(TMDB_BASE_URL, "/") . "/" . ltrim($endpoint, "/");

    if (!empty($parametros)) {
        $url .= "?" . http_build_query($parametros);
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . TMDB_READ_ACCESS_TOKEN,
            "accept: application/json"
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    $resposta = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($resposta === false) {
        error_log("Erro de conexão com a TMDB: " . curl_error($curl));
        curl_close($curl);
        return [];
    }

    curl_close($curl);

    if ($status < 200 || $status >= 300) {
        error_log("TMDB retornou HTTP " . $status);
        return [];
    }

    $dados = json_decode($resposta, true);

    return is_array($dados) ? $dados : [];
}

/**
 * Monta a URL de uma imagem.
 */
function imagemTMDB($caminho, $tamanho = "w500")
{
    if (empty($caminho)) {
        return "";
    }

    return rtrim(TMDB_IMAGE_BASE_URL, "/") .
        "/" . $tamanho . $caminho;
}

/**
 * Busca filmes populares.
 */
function buscarFilmesPopulares($pagina = 1)
{
    return consultarTMDB("/movie/popular", [
        "language" => "pt-BR",
        "page" => max(1, (int) $pagina)
    ]);
}

/**
 * Busca filmes de terror.
 */
function buscarFilmesTerror($pagina = 1)
{
    return consultarTMDB("/discover/movie", [
        "with_genres" => 27,
        "sort_by" => "popularity.desc",
        "language" => "pt-BR",
        "page" => max(1, (int) $pagina),
        "include_adult" => "false"
    ]);
}



/**
 * Pesquisa filmes na TMDB em português ou inglês.
 */
function buscarFilmePorTituloTMDB($titulo, $idioma = "pt-BR")
{
    $titulo = trim((string) $titulo);

    if ($titulo === "") {
        return [];
    }

    $dados = consultarTMDB("/search/movie", [
        "query" => $titulo,
        "language" => $idioma,
        "include_adult" => "false",
        "page" => 1
    ]);

    return $dados["results"] ?? [];
}





/**
 * Busca detalhes, elenco, vídeos e classificação indicativa.
 */
function buscarDetalhesFilme($id)
{
    $detalhes = consultarTMDB("/movie/" . (int) $id, [
        "language" => "pt-BR",
        "append_to_response" => "credits,videos,release_dates"
    ]);

    return $detalhes;
}



/**
 * Sincroniza um filme pelo ID da TMDB.
 */
function sincronizarFilmeTMDB(PDO $pdo, array $filme)
{
    if (empty($filme["id"]) || empty($filme["title"])) {
        return false;
    }

    $tmdbId = (int) $filme["id"];
    $titulo = $filme["title"];
    $sinopse = $filme["overview"] ?? "";
    $poster = imagemTMDB($filme["poster_path"] ?? "");

    $ano = !empty($filme["release_date"])
        ? substr($filme["release_date"], 0, 4)
        : null;

    $sql = "
        INSERT INTO filmes (
            tmdb_id,
            titulo,
            sinopse,
            poster_url,
            ano_lancamento
        )
        VALUES (
            :tmdb_id,
            :titulo,
            :sinopse,
            :poster_url,
            :ano_lancamento
        )
        ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            sinopse = VALUES(sinopse),
            poster_url = CASE
                WHEN VALUES(poster_url) IS NOT NULL
                     AND VALUES(poster_url) <> ''
                THEN VALUES(poster_url)
                ELSE poster_url
            END,
            ano_lancamento = COALESCE(
                VALUES(ano_lancamento),
                ano_lancamento
            )
    ";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        "tmdb_id" => $tmdbId,
        "titulo" => $titulo,
        "sinopse" => $sinopse,
        "poster_url" => $poster !== "" ? $poster : null,
        "ano_lancamento" => $ano
    ]);

    $consultaId = $pdo->prepare(
        "SELECT id FROM filmes WHERE tmdb_id = :tmdb_id LIMIT 1"
    );

    $consultaId->execute(["tmdb_id" => $tmdbId]);

    $idLocal = $consultaId->fetchColumn();

    return $idLocal ? (int) $idLocal : false;
}
