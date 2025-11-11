<?php

    $articlesArray = json_decode(file_get_contents('articles.json'), true);

    if(isset($_GET['id'])) {
        $idToUpdate = $_GET['id'];
        foreach ($articlesArray as $index => $article) {
            if ($article['id'] == $idToUpdate) {
                $articleToUpdate = $article;
                $articleIndex = $index;

                break;
            }
        }



        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $articlesArray[$articleIndex]['title'] = $_POST['title'];
            $articlesArray[$articleIndex]['content'] = $_POST['content'];
            $articlesArray[$articleIndex]['date'] = date('Y-m-d');

            file_put_contents('articles.json', json_encode($articlesArray, JSON_PRETTY_PRINT));
            header("Location: adminView.php" );
            exit;
        }
    }
        

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Article</title>
</head>
<body>
    <h2>Edit Article</h2>

   
        <form method="POST">
            <label>Title:</label><br>
            <input type="text" name="title" value="<?php echo htmlspecialchars($articleToUpdate['title']); ?>"><br><br>

            <label>Content:</label><br>
            <input type = "text" name="content" value ="<?php echo htmlspecialchars($articleToUpdate['content']); ?>"  ><br><br>

            <input type="submit" value="Save Changes">
        </form>

</body>
</html>