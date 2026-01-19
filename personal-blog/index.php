<?php
require __DIR__ . '/config/db.php';

$pdo = get_pdo();
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = null;

if ($articleId > 0) {
    $stmt = $pdo->prepare('SELECT id, title, content, created_at FROM articles WHERE id = ?');
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    if (!$article) {
        http_response_code(404);
    }
}

$articles = $pdo->query('SELECT id, title, created_at FROM articles ORDER BY created_at DESC')->fetchAll();

$monthNames = [
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre',
];

$calendar = [];
foreach ($articles as $calendarArticle) {
    $timestamp = strtotime($calendarArticle['created_at']);
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $calendar[$year][$month][] = [
        'id' => $calendarArticle['id'],
        'title' => $calendarArticle['title'],
        'date' => date('Y-m-d', $timestamp),
    ];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLOG-PERSONAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="brand">BLOG-PERSONAL</div>
            </div>
        </div>

        <div class="layout">
            <div class="articles-column">
                <?php if ($articleId > 0 && !$article) { ?>
                    <div class="notice">Articulo no encontrado.</div>
                <?php } ?>

                <?php if ($article) { ?>
                    <article class="card">
                        <h2><?= htmlspecialchars($article['title'] ?? '') ?></h2>
                        <div class="meta">Publicado el <?= date('Y-m-d', strtotime($article['created_at'])) ?></div>
                        <p><?= nl2br(htmlspecialchars($article['content'] ?? '')) ?></p>
                        <div class="actions">
                            <a class="button light" href="index.php">Volver al listado</a>
                        </div>
                    </article>
                <?php } ?>

                <?php if (!$article) { ?>
                    <?php if (!$articles) { ?>
                        <div class="notice">No hay articulos publicados todavia.</div>
                    <?php } ?>

                    <?php foreach ($articles as $articleItem) { ?>
                        <article class="card">
                            <h2>
                                <a href="index.php?id=<?= (int) $articleItem['id'] ?>">
                                    <?= htmlspecialchars($articleItem['title']) ?>
                                </a>
                            </h2>
                            <div class="meta">Publicado el <?= date('Y-m-d', strtotime($articleItem['created_at'])) ?></div>
                        </article>
                    <?php } ?>
                <?php } ?>
            </div>

            <aside class="calendar">
                <h3>Calendario</h3>
                <?php if (!$calendar) { ?>
                    <div class="meta">Sin publicaciones.</div>
                <?php } ?>
                <?php foreach ($calendar as $year => $months) { ?>
                    <details>
                        <summary><?= htmlspecialchars($year) ?></summary>
                        <ul>
                            <?php foreach ($months as $month => $posts) { ?>
                                <li>
                                    <strong><?= htmlspecialchars($monthNames[$month]) ?></strong>
                                    <ul>
                                        <?php foreach ($posts as $post) { ?>
                                            <li>
                                                <a href="index.php?id=<?= (int) $post['id'] ?>">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </a>
                                                <span class="meta"><?= htmlspecialchars($post['date']) ?></span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                        </ul>
                    </details>
                <?php } ?>
            </aside>
        </div>
    </div>
</body>
</html>
