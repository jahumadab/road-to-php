<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php';

$pdo = get_pdo();
$action = $_GET['action'] ?? '';
$error = '';
$feedback = '';
$editArticle = null;

if ($action === 'logout') {
    logout_admin();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $action;

    if ($action === 'login') {
        $user = trim($_POST['user'] ?? '');
        $pass = trim($_POST['pass'] ?? '');

        if (login_admin($user, $pass)) {
            header('Location: admin.php');
            exit;
        }

        $error = 'Credenciales incorrectas.';
    } else {
        if (!is_admin()) {
            header('Location: admin.php');
            exit;
        }

        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if ($title !== '' && $content !== '') {
                $stmt = $pdo->prepare('INSERT INTO articles (title, content, created_at) VALUES (?, ?, NOW())');
                $stmt->execute([$title, $content]);
                header('Location: admin.php');
                exit;
            }
            $feedback = 'Completa titulo y contenido.';
            $action = 'new';
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if ($id > 0 && $title !== '' && $content !== '') {
                $stmt = $pdo->prepare('UPDATE articles SET title = ?, content = ? WHERE id = ?');
                $stmt->execute([$title, $content, $id]);
                header('Location: admin.php');
                exit;
            }
            $feedback = 'No se pudieron guardar los cambios.';
            $action = 'edit';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
                $stmt->execute([$id]);
            }
            header('Location: admin.php');
            exit;
        }
    }
}

if (is_admin() && $action === 'edit') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, title, content FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $editArticle = $stmt->fetch();
    }
    if (!$editArticle) {
        http_response_code(404);
        $feedback = 'Articulo no encontrado.';
    }
}

$articles = $pdo->query('SELECT id, title, created_at FROM articles ORDER BY created_at DESC')->fetchAll();
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
                <div class="badge">Admin</div>
                <div class="brand">BLOG-PERSONAL</div>
            </div>
            <div class="actions">
                <?php if (is_admin()) { ?>
                    <a class="button" href="admin.php?action=new">Nuevo articulo</a>
                    <a class="button light" href="admin.php?action=logout">Salir</a>
                <?php } else { ?>
                    <a class="button light" href="index.php">Volver</a>
                <?php } ?>
            </div>
        </div>

        <?php if (!is_admin()) { ?>
            <div class="card login-card">
                <form class="form login-form" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label>Usuario</label>
                        <input type="text" name="user" required>
                    </div>
                    <div>
                        <label>Contrasena</label>
                        <input type="password" name="pass" required>
                    </div>
                    <?php if ($error) { ?>
                        <div class="notice"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>
                    <button class="button" type="submit">Ingresar</button>
                </form>
            </div>
        <?php } else { ?>
            <?php if ($feedback) { ?>
                <div class="notice"><?= htmlspecialchars($feedback) ?></div>
            <?php } ?>

            <?php if ($action === 'new') { ?>
                <div class="card">
                    <form class="form edit-form" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label>Titulo</label>
                            <input type="text" name="title" required>
                        </div>
                        <div>
                            <label>Contenido</label>
                            <textarea name="content" required></textarea>
                        </div>
                        <button class="button" type="submit">Publicar</button>
                    </form>
                </div>
            <?php } ?>

            <?php if ($action === 'edit' && $editArticle) { ?>
                <div class="card">
                    <form class="form" method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $editArticle['id'] ?>">
                        <div>
                            <label>Titulo</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($editArticle['title']) ?>" required>
                        </div>
                        <div>
                            <label>Contenido</label>
                            <textarea name="content" required><?= htmlspecialchars($editArticle['content']) ?></textarea>
                        </div>
                        <button class="button" type="submit">Guardar cambios</button>
                    </form>
                </div>
            <?php } ?>

            <div class="articles-column">
                <?php if (!$articles) { ?>
                    <div class="notice">Todavia no hay articulos. Crea el primero.</div>
                <?php } ?>

                <?php foreach ($articles as $article) { ?>
                    <article class="card">
                        <h2>
                            <a href="index.php?id=<?= (int) $article['id'] ?>">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h2>
                        <div class="meta">Publicado el <?= date('Y-m-d', strtotime($article['created_at'])) ?></div>
                        <div class="actions">
                            <a class="button alt" href="admin.php?action=edit&id=<?= (int) $article['id'] ?>">Editar</a>
                            <form method="POST" onsubmit="return confirm('Eliminar este articulo?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                <button class="button light" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>
