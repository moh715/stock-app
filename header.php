<?php
$user = $_SESSION['user_id'] ?? null;
?>

<nav>
    <a href="index.php">Index</a>
    <a href="search.php">Search</a>
    <a href="pinned.php">Pinned</a>

    <?php if ($user): ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>