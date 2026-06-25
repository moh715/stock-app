<?php
require_once 'config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm']);

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } 
    elseif($password_confirm != $password){
        $error = 'confirm password and password should be the same';
    }
    elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = get_db();

        $check = $db->prepare('SELECT id FROM users WHERE name = ?');
        $check->execute([$username]);

        if ($check->fetch()) {
            $error = 'Username is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (name, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            $success = 'Account created! You can now log in.';
        }
    }
}
?>
<?php include 'php/nav.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — StockView</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-wrap">
    <h1>Create account</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
            <br><a href="login.php">Go to Login →</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                >
            </div>
            <div class="form-group">
                <label for="password_confirm">confirm Password</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    autocomplete="new-password"
                >
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Register</button>
        </form>
    <?php endif; ?>

    <p class="form-footer">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>
