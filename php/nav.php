<?php

require_once __DIR__ . '/../config.php';
$active = $active ?? '';
?>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">StockView</a>
        <ul class="nav-links">
            <li>
                <a href="index.php"  class="<?= $active === 'index'  ? 'active' : '' ?>">Home</a>
            </li>
            <li>
                <a href="search.php" class="<?= $active === 'search' ? 'active' : '' ?>">Search</a>
            </li>
            <li>
                <a href="news.php" class="<?= $active === 'news' ? 'active' : '' ?>">News</a>
            </li>
            <li>
                <a href="pinned.php" class="<?= $active === 'pinned' ? 'active' : '' ?>">Pinned</a>
            </li>

            <?php if (is_logged_in()): ?>
                <li>
                    <form method="POST" action="php/logout.php" style="margin:0">
                        <button type="submit" class="btn-logout">Logout</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="login.php"    class="<?= $active === 'login'    ? 'active' : '' ?>">Login</a></li>
                <li><a href="register.php" class="<?= $active === 'register' ? 'active' : '' ?>">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>