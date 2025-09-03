<?php
require_once __DIR__ . '/../config/session.php';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">CarEase</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cars.php">Our Cars</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item"><a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
                    <li class="nav-item"><a href="register.php" class="nav-link">Register</a></li>
                    <li class="nav-item"><a href="admin/login.php" class="nav-link">Admin Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 