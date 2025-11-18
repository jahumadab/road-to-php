<?php
include("db.php");


if(isset($_GET['id'])){
    $idToUpdate = $_GET['id'];

    $query = "SELECT * FROM posts WHERE id = $idToUpdate";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);


    if(!$row){
    http_response_code(404);
    echo json_encode(["error" => "Post not found"]);
    exit;
    }

    http_response_code(200);
    echo json_encode($row);

}
else{
    $query = "SELECT * FROM posts";
    $result = mysqli_query($conn, $query);
    
    $post = [];
    while($row = mysqli_fetch_assoc($result)){
        $post[] = $row;
    }

    http_response_code(200);
    echo json_encode($post);

}





?>
