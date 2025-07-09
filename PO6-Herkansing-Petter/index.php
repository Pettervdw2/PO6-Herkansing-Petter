<?php
session_start();
require_once 'dbconnect.php';

// Filter- en sorteervelden ophalen
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Sorteeropties bepalen
switch ($sort) {
    case 'name_asc':
        $orderBy = 'name ASC';
        break;
    case 'name_desc':
        $orderBy = 'name DESC';
        break;
    case 'price_asc':
        $orderBy = 'price ASC';
        break;
    case 'price_desc':
        $orderBy = 'price DESC';
        break;
    case 'desc_asc':
        $orderBy = 'description ASC';
        break;
    case 'desc_desc':
        $orderBy = 'description DESC';
        break;
    default:
        $orderBy = 'name ASC';
}

// Query opbouwen
$sql = 'SELECT id, name, description, price FROM motors';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE name LIKE ?';
    $params[] = "%$search%";
}
$sql .= " ORDER BY $orderBy";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$motors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afbeeldingen per motor-id
$images = [
    1 => 'img/suzuki.jpg',
    2 => 'img/ducati.jpg',
    3 => 'img/aprilia.jpg',
    4 => 'img/kawasaki.jpg',
    // Voeg meer toe indien nodig
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Homepagina</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#motors">Motors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Log in</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Registreren</a></li>
                    <?php endif; ?>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><span class="nav-link disabled">Hallo, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h1 class="mb-4">Welkom op de homepagina!</h1>
        <section id="motors">
            <h2>Motors</h2>
            <form class="row g-3 align-items-end mb-4" method="get">
                <div class="col-md-4">
                    <label for="search" class="form-label">Zoek op naam</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Bijv. Suzuki">
                </div>
                <div class="col-md-4">
                    <label for="sort" class="form-label">Sorteer op</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Naam (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Naam (Z-A)</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prijs (Laag-Hoog)</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prijs (Hoog-Laag)</option>
                        <option value="desc_asc" <?= $sort === 'desc_asc' ? 'selected' : '' ?>>Beschrijving (A-Z)</option>
                        <option value="desc_desc" <?= $sort === 'desc_desc' ? 'selected' : '' ?>>Beschrijving (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-2"></i>Filteren</button>
                </div>
            </form>
            <div class="row">
                <?php foreach ($motors as $motor): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php
                            $imgSrc = isset($images[$motor['id']]) && file_exists(__DIR__ . '/' . $images[$motor['id']])
                                ? $images[$motor['id']]
                                : 'https://via.placeholder.com/400x250?text=Motor';
                            ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" class="card-img-top" alt="Motor foto">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($motor['name']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($motor['description']) ?></p>
                                <p class="card-text fw-bold">Prijs: &euro;<?= number_format($motor['price'], 2, ',', '.') ?></p>
                                <a href="detail.php?id=<?= urlencode($motor['id']) ?>" class="btn btn-primary">Bekijk meer</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 