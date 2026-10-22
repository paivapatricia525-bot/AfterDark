
<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "conexao.php";

$filme_id = filter_input(INPUT_POST, "filme_id", FILTER_VALIDATE_INT);
$acao = $_POST["acao"] ?? "";

if (!$filme_id || !in_array($acao, ["adicionar", "remover"], true)) {
    header("Location: catalogo.php");
    exit;
}

$verificar = $pdo->prepare(
    "SELECT id FROM filmes WHERE id = :id"
);
$verificar->execute(["id" => $filme_id]);

if (!$verificar->fetch()) {
    header("Location: catalogo.php");
    exit;
}

if ($acao === "adicionar") {
    $sql = "
        INSERT IGNORE INTO favoritos (usuario_id, filme_id)
        VALUES (:usuario_id, :filme_id)
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        "usuario_id" => $_SESSION["usuario_id"],
        "filme_id" => $filme_id
    ]);
} else {
    $sql = "
        DELETE FROM favoritos
        WHERE usuario_id = :usuario_id
        AND filme_id = :filme_id
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        "usuario_id" => $_SESSION["usuario_id"],
        "filme_id" => $filme_id
    ]);
}

$origem = $_POST["origem"] ?? "catalogo";

if ($origem === "detalhes") {
    header("Location: detalhes.php?id=" . $filme_id);
} elseif ($origem === "favoritos") {
    header("Location: favoritos.php");
} else {
    header("Location: catalogo.php");
}

exit;
?>
