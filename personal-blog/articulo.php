<?php 

$articlesArray = json_decode(file_get_contents('articles.json'), true); 

$id = $_GET['id'] ?? null;

if (!$id || !isset($articlesArray[$id - 1])) {
    echo "<h2>Artículo no encontrado</h2>";
    exit;
}
$art = $articlesArray[$id - 1];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php $art['title'] ?></title>
</head>
<body>
    <a href="index.php">Volver al listado de artículos</a>
    <h1><?php echo $art['title']; ?></h1>
    <p><em>Publicado el <?php echo $art['date']; ?></em></p>
    <div>
        <p> <?php echo nl2br($art['content']); ?> </p>
    </div>
    
</body>
</html>
