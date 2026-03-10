<?php
require_once 'config.php';

// if already logged in, send to admin
if (!empty($_SESSION['logged_in'])) {
    header('Location: admin.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';

    $valid = false;

    // first try the static config credentials
    if ($u === $admin_user && password_verify($p, $admin_pass_hash)) {
        $valid = true;
    }

    // if not yet valid and `users` table exists, check there
    if (!$valid) {
        try {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$u]);
            if ($row = $stmt->fetch()) {
                if (password_verify($p, $row['password_hash'])) {
                    $valid = true;
                }
            }
        } catch (\PDOException $e) {
            // table might not exist; ignore
        }
    }

    if ($valid) {
        // good credentials
        $_SESSION['logged_in'] = true;
        // regenerate id to mitigate fixation
        session_regenerate_id(true);
        header('Location: admin.php');
        exit;
    } else {
        $errors[] = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - PEZA Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('images/PEZA-background.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            background-color: rgba(255,255,255,0.9);
            padding: 2rem;
            max-width: 400px;
            margin-top: 100px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Administrator Login</h1>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Log in</button>
    </form>
    <p class="mt-3"><a href="index.php">Back to directory</a></p>
</div>
</body>
</html>