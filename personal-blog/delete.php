<?php

    $articlesArray = json_decode(file_get_contents('articles.json'), true);

    if(isset($_GET['id'])) {
        $idToDelete = $_GET['id'];
        foreach ($articlesArray as $index => $article) {
            if ($article['id'] == $idToDelete) {
                unset($articlesArray[$index]);
                break;
            }
        }
        // Reindex the array to maintain proper indices
        $articlesArray = array_values(array: $articlesArray);
        file_put_contents('articles.json', json_encode($articlesArray, JSON_PRETTY_PRINT));
    }


    header("Location: adminView.php" );
?>

