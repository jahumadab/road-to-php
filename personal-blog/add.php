<?php

    if(isset($_POST["title"]) && isset($_POST["content"])) {
        $articlesArray = json_decode(file_get_contents('articles.json'), true); 
        $newArticle = [

            'id' => count($articlesArray) + 1,
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'date' => date('Y-m-d')
        ];
    } else {
        exit;
    }




$articlesArray[] = $newArticle;
file_put_contents('articles.json', json_encode($articlesArray, JSON_PRETTY_PRINT));

header("Location: adminView.php" );
?>