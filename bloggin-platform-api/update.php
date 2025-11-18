<?php
include("db.php");



if(isset($_GET['id'])){
    $idToUpdate = $_GET['id'];

}

// extraer el post actual de la bd
$data = json_decode(file_get_contents("php://input"), true);
$query = "SELECT * FROM posts WHERE id = $idToUpdate";
$result = mysqli_query($conn, $query);
$post = mysqli_fetch_array($result);



// validar que llegaron datos
if (!($post)) {
    http_response_code(400);
    echo json_encode(["error" => "error", "Post not found"]);
    exit;
}

$title = $data["title"];
$content = $data["content"];
$category = $data["category"];
$tags = json_encode($data["tags"]); // convertir array a JSON
$updateAt = date("Y-m-d H:i:s");

$query = "UPDATE posts SET 
          title = '$title', 
          content = '$content', 
          category = '$category', 
          tags = '$tags', 
          updatedAt = '$updateAt' 
          WHERE id = " . $idToUpdate;

$result = mysqli_query($conn, $query);

if(!$result){
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    exit;
}

http_response_code(200);
echo json_encode([
    "id"        => $idToUpdate,
    "title"     => $title,
    "content"   => $content,
    "category"  => $category,
    "tags"      => $tags,   // como array, no el JSON string
    "createdAt" => $post["createdAt"],
    "updatedAt" => $updateAt,
]);

?>
