<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <h1>My Personal Blog</h1>
    <div class = "listar-articulos">
        
    <?php
    
    $articlesArray = json_decode(file_get_contents('articles.json'), true); 
    foreach ($articlesArray as $line) {
    ?>
        <div class="articulo">
            <h2><a href="articulo.php?id=<?= $line['id'] ?>"><?= $line['title'] ?>, <?= $line['date'] ?></a></h2>
        </div>
    <?php
    }
    
    ?>

    </div>
    
</body>
</html>