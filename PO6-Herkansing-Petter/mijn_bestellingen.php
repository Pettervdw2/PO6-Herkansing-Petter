<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Sorteeropties verwerken
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = 'order_date DESC';
if ($sort === 'date_asc') {
    $orderBy = 'order_date ASC';
} elseif ($sort === 'product_asc') {
    $orderBy = 'JSON_UNQUOTE(JSON_EXTRACT(order_data, "$[0].name")) ASC, order_date DESC';
} elseif ($sort === 'product_desc') {
    $orderBy = 'JSON_UNQUOTE(JSON_EXTRACT(order_data, "$[0].name")) DESC, order_date DESC';
}
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY $orderBy");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Navbar code (gekopieerd uit profile.php, alleen de navbar)
$isAdmin = false;
$username = '';
if (isset($_SESSION['user_id'])) {
    $stmtUser = $db->prepare('SELECT username, role FROM users WHERE id = ?');
    $stmtUser->execute([$_SESSION['user_id']]);
    $row = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $username = $row['username'];
        if ($row['role'] === 'admin') {
            $isAdmin = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Bestellingen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="https://via.placeholder.com/40x40?text=Logo" alt="Logo" width="40" height="40" class="me-2 rounded-circle">
            <span class="fs-4 fw-bold text-white">MotoSports</span>
            <i class="bi bi-bicycle fs-3 text-light ms-2"></i>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="mijn_bestellingen.php"><i class="bi bi-box-seam"></i> Mijn Bestellingen</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profiel</a></li>
                    <li class="nav-item">
                        <span class="nav-link disabled">
                            Hallo, <?= htmlspecialchars($username) ?>
                            <?php if ($isAdmin): ?>
                                <span class="badge bg-danger ms-2">Admin</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item"><a class="nav-link text-danger fw-bold" href="admin_orders.php"><i class="bi bi-clipboard-data"></i> Admin Orders</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Log in</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Registreren</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
    <h1 class="mb-4">Mijn Bestellingen</h1>
    <form class="mb-3" method="get">
        <div class="row g-2 align-items-center">
            <div class="col-auto"><label for="sort" class="col-form-label">Sorteren op:</label></div>
            <div class="col-auto">
                <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Datum (nieuwste eerst)</option>
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Datum (oudste eerst)</option>
                    <option value="product_asc" <?= $sort === 'product_asc' ? 'selected' : '' ?>>Productnaam (A-Z)</option>
                    <option value="product_desc" <?= $sort === 'product_desc' ? 'selected' : '' ?>>Productnaam (Z-A)</option>
                </select>
            </div>
        </div>
    </form>
    <?php if (count($orders) === 0): ?>
        <div class="alert alert-info">Je hebt nog geen bestellingen geplaatst.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Datum</th>
                        <th>Totaal</th>
                        <th>Inhoud</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= date('d-m-Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>&euro;<?= number_format($order['total'], 2, ',', '.') ?></td>
                        <td>
                            <ul class="mb-0">
                                <?php foreach (json_decode($order['order_data'], true) as $item): ?>
                                    <li><?= htmlspecialchars($item['name']) ?> x <?= $item['aantal'] ?? 1 ?> (&euro;<?= number_format($item['price'], 2, ',', '.') ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><span class="badge bg-secondary">In behandeling</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html> 