<?php
session_start();
require_once 'dbconnect.php';

// Controleer of gebruiker is ingelogd en admin is
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $db->prepare('SELECT role, username FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="container py-5"><div class="alert alert-danger">Geen toegang</div></div>';
    exit;
}

// Sorteeropties verwerken
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = 'o.order_date DESC';
if ($sort === 'date_asc') {
    $orderBy = 'o.order_date ASC';
} elseif ($sort === 'product_asc') {
    $orderBy = 'JSON_UNQUOTE(JSON_EXTRACT(o.order_data, "$[0].name")) ASC, o.order_date DESC';
} elseif ($sort === 'product_desc') {
    $orderBy = 'JSON_UNQUOTE(JSON_EXTRACT(o.order_data, "$[0].name")) DESC, o.order_date DESC';
}

// Haal bestellingen op
$stmt = $db->query("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY $orderBy");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isAdmin = false;
$username = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare('SELECT username, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $username = $row['username'];
        if ($row['role'] === 'admin') {
            $isAdmin = true;
        }
    }
}

// Order verwijderen (admin)
if ($isAdmin && isset($_POST['delete_order_id'])) {
    $deleteOrderId = (int)$_POST['delete_order_id'];
    $stmt = $db->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->execute([$deleteOrderId]);
    // Refresh orders
    $stmt = $db->query("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY $orderBy");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bestellingen (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <?php
                        $isAdmin = false;
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
                            $stmt->execute([$_SESSION['user_id']]);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($row && $row['role'] === 'admin') {
                                $isAdmin = true;
                            }
                        }
                        ?>
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
        <h1 class="mb-4">Alle bestellingen</h1>
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
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Gebruiker</th>
                        <th>E-mail</th>
                        <th>Datum</th>
                        <th>Totaal</th>
                        <th>Inhoud</th>
                        <th>Actie</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>&euro;<?= number_format($order['total'], 2, ',', '.') ?></td>
                        <td>
                            <ul class="mb-0">
                                <?php foreach (json_decode($order['order_data'], true) as $item): ?>
                                    <li><?= htmlspecialchars($item['name']) ?> (&euro;<?= number_format($item['price'], 2, ',', '.') ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirm('Weet je zeker dat je deze order wilt verwijderen?');">
                                <input type="hidden" name="delete_order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 