
<?php

require_once __DIR__ . "/tmdb.php";

$filmes = [
    ["titulo" => "A Aldeia", "ano" => 2004],
    ["titulo" => "A Cabana na Floresta", "ano" => 2011],
    ["titulo" => "A Centopeia Humana", "ano" => 2009],
    ["titulo" => "Crimes de Carne", "ano" => 2003],
    ["titulo" => "Enigma de Outro Mundo: O Prelúdio", "ano" => 2011],
    ["titulo" => "O Brinquedo Assassino", "ano" => 1988],
    ["titulo" => "O Vazio", "ano" => 2016],
    ["titulo" => "Sociedade", "ano" => 1989]
];

echo "<h1>Teste de busca TMDB — AfterDark</h1>";

foreach ($filmes as $filme) {
    echo "<hr>";
    echo "<h3>" .
        htmlspecialchars($filme["titulo"], ENT_QUOTES, "UTF-8") .
        " (" . $filme["ano"] . ")</h3>";

    foreach (["pt-BR", "en-US"] as $idioma) {
        $resultados = buscarFilmePorTituloTMDB(
            $filme["titulo"],
            $idioma
        );

        echo "<p>Idioma: " . $idioma . "</p>";

        if (empty($resultados)) {
            echo "<p>Nenhum resultado retornado.</p>";
            continue;
        }

        echo "<ul>";

        foreach (array_slice($resultados, 0, 5) as $resultado) {
            $titulo = $resultado["title"] ?? "Sem título";
            $ano = !empty($resultado["release_date"])
                ? substr($resultado["release_date"], 0, 4)
                : "Sem ano";

            $poster = !empty($resultado["poster_path"])
                ? "Sim"
                : "Não";

            echo "<li>";
            echo htmlspecialchars($titulo, ENT_QUOTES, "UTF-8");
            echo " (" . htmlspecialchars($ano) . ")";
            echo " — ID: " . (int) ($resultado["id"] ?? 0);
            echo " — Pôster disponível: " . $poster;
            echo "</li>";
        }

        echo "</ul>";
    }
}
