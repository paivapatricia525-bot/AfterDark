
<?php

require_once __DIR__ . "/tmdb.php";

$consultas = [
    "Crimes de Carne 2003 horror",
    "horror film human experiments 2003",
    "medical experiments horror film 2003",
    "body horror 2003",
    "Flesh horror film 2003"
];

echo "<h1>Pesquisa de títulos — AfterDark</h1>";

foreach ($consultas as $titulo) {
    echo "<hr><h2>" .
        htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") .
        "</h2>";

    foreach (["pt-BR", "en-US"] as $idioma) {
        $resultados = buscarFilmePorTituloTMDB($titulo, $idioma);

        echo "<h3>Idioma: " . $idioma . "</h3>";

        if (empty($resultados)) {
            echo "<p>Nenhum resultado.</p>";
            continue;
        }

        echo "<ul>";

        foreach (array_slice($resultados, 0, 5) as $filme) {
            $nome = $filme["title"] ?? "Sem título";
            $ano = !empty($filme["release_date"])
                ? substr($filme["release_date"], 0, 4)
                : "Sem ano";

            $poster = !empty($filme["poster_path"])
                ? "Sim"
                : "Não";

            echo "<li>";
            echo htmlspecialchars($nome, ENT_QUOTES, "UTF-8");
            echo " (" . htmlspecialchars($ano, ENT_QUOTES, "UTF-8") . ")";
            echo " — ID: " . (int) ($filme["id"] ?? 0);
            echo " — Pôster: " . $poster;
            echo "</li>";
        }

        echo "</ul>";
    }
}
