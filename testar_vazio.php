
<?php

require_once __DIR__ . "/tmdb.php";

$id = 1138436;

$filme = buscarDetalhesFilme($id);

echo "<h1>Consulta do filme no TMDB</h1>";

if (!$filme) {
    exit("<p>Não foi possível consultar o filme.</p>");
}

echo "<pre>";
echo htmlspecialchars(
    print_r([
        "id" => $filme["id"] ?? null,
        "titulo" => $filme["title"] ?? null,
        "titulo_original" => $filme["original_title"] ?? null,
        "data_lancamento" => $filme["release_date"] ?? null,
        "poster_path" => $filme["poster_path"] ?? null,
        "backdrop_path" => $filme["backdrop_path"] ?? null
    ], true),
    ENT_QUOTES,
    "UTF-8"
);
echo "</pre>";
