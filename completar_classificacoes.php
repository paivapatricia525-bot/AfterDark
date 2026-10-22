
<?php
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/tmdb.php";

set_time_limit(120);

$lote = 5;
$inicio = max(0, (int) ($_GET["inicio"] ?? 0));

$consulta = $pdo->query("
    SELECT id, titulo, tmdb_id
    FROM filmes
    WHERE classificacao IS NULL
       OR TRIM(classificacao) = ''
    ORDER BY id
");

$pendentes = $consulta->fetchAll(PDO::FETCH_ASSOC);
$total = count($pendentes);
$loteAtual = array_slice($pendentes, $inicio, $lote);

function obterClassificacaoBrasil(array $detalhes): string
{
    $datas = $detalhes["release_dates"]["results"] ?? [];

    foreach ($datas as $pais) {
        if (($pais["iso_3166_1"] ?? "") !== "BR") {
            continue;
        }

        foreach (($pais["release_dates"] ?? []) as $lancamento) {
            $certificado = trim($lancamento["certification"] ?? "");

            if ($certificado !== "") {
                return $certificado;
            }
        }
    }

    return "";
}

$relatorio = [];

foreach ($loteAtual as $filme) {
    if (empty($filme["tmdb_id"])) {
        $relatorio[] = $filme["titulo"] . " — sem ID do TMDB.";
        continue;
    }

    $detalhes = buscarDetalhesFilme($filme["tmdb_id"]);

    if (empty($detalhes["id"])) {
        $relatorio[] = $filme["titulo"] . " — não foi possível consultar.";
        continue;
    }

    $classificacao = obterClassificacaoBrasil($detalhes);

    if ($classificacao === "") {
        $relatorio[] = $filme["titulo"] . " — classificação brasileira indisponível.";
        continue;
    }

    $atualizar = $pdo->prepare("
        UPDATE filmes
        SET classificacao = :classificacao
        WHERE id = :id
          AND (classificacao IS NULL OR TRIM(classificacao) = '')
    ");

    $atualizar->execute([
        "classificacao" => $classificacao,
        "id" => $filme["id"]
    ]);

    $relatorio[] = $filme["titulo"] . " — classificação: " . $classificacao;
}

$proximo = $inicio + count($loteAtual);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar classificações | AfterDark</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="pagina-catalogo">
    <h1>Classificações indicativas</h1>
    <p>AfterDark · TMDB</p>

    <p>
        Verificados <?= min($proximo, $total) ?>
        de <?= $total ?> filmes pendentes.
    </p>

    <progress
        value="<?= min($proximo, $total) ?>"
        max="<?= max(1, $total) ?>"
        style="width: 100%;"
    ></progress>

    <h2>Resultado deste lote</h2>
    <ul>
        <?php foreach ($relatorio as $linha): ?>
            <li><?= htmlspecialchars($linha, ENT_QUOTES, "UTF-8") ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($proximo < $total && count($loteAtual) > 0): ?>
        <a class="botao-principal"
           href="completar_classificacoes.php?inicio=<?= $proximo ?>">
            Processar próximo lote
        </a>
    <?php else: ?>
        <p>Verificação concluída.</p>
    <?php endif; ?>
</main>
</body>
</html>
