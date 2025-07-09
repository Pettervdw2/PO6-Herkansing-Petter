<?php
require_once 'dbconnect.php';

// Haal het id uit de URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Haal de motor op uit de database
$stmt = $db->prepare('SELECT id, name, description, price FROM motors WHERE id = ?');
$stmt->execute([$id]);
$motor = $stmt->fetch(PDO::FETCH_ASSOC);

// Vaste afbeeldingen per card (in volgorde van id, let op: index = id-1)
$images = [
    1 => 'img/suzuki.jpg',
    2 => 'img/ducati.jpg',
    3 => 'img/aprilia.jpg',
    4 => 'img/kawasaki.jpg',
    // Voeg meer toe indien nodig
];

// Bepaal de afbeelding
$imgSrc = isset($images[$id]) && file_exists(__DIR__ . '/' . $images[$id])
    ? $images[$id]
    : 'https://via.placeholder.com/600x350?text=Motor';

// Review toevoegen
$reviewSuccess = false;
$reviewError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['naam'], $_POST['beoordeling'], $_POST['bericht']) && !isset($_POST['sort'])) {
    $naam = trim($_POST['naam']);
    $beoordeling = (int)$_POST['beoordeling'];
    $bericht = trim($_POST['bericht']);
    if ($naam && $beoordeling >= 1 && $beoordeling <= 5 && $bericht) {
        $stmt = $db->prepare('INSERT INTO reviews (motor_id, naam, beoordeling, bericht, datum) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$id, $naam, $beoordeling, $bericht]);
        $reviewSuccess = true;
    } else {
        $reviewError = 'Vul alle velden correct in.';
    }
}

// Sorteeropties ophalen
$sort = isset($_POST['sort']) ? $_POST['sort'] : (isset($_GET['sort']) ? $_GET['sort'] : 'date_desc');
$orderBy = 'datum DESC';
switch ($sort) {
    case 'date_asc':
        $orderBy = 'datum ASC';
        break;
    case 'rating_desc':
        $orderBy = 'beoordeling DESC, datum DESC';
        break;
    case 'rating_asc':
        $orderBy = 'beoordeling ASC, datum DESC';
        break;
    default:
        $orderBy = 'datum DESC';
}

// Reviews ophalen
$stmt = $db->prepare("SELECT naam, beoordeling, bericht, datum FROM reviews WHERE motor_id = ? ORDER BY $orderBy");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Motor detail</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .motor-hero-img {
            max-height: 350px;
            object-fit: cover;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
        }
        .review-card {
            border: none;
            border-radius: 1rem;
            background: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .review-form {
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            background: #fff;
            padding: 2rem 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="https://via.placeholder.com/40x40?text=Logo" alt="Logo" width="40" height="40" class="me-2 rounded-circle">
                <i class="bi bi-bicycle fs-3 text-light"></i>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#motors">Motors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><span class="nav-link disabled">Hallo, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Log in</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Registreren</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <?php if ($motor): ?>
            <div class="row align-items-center mb-5 g-4">
                <div class="col-lg-6">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="motor-hero-img mb-3 mb-lg-0" alt="Motor foto">
                </div>
                <div class="col-lg-6">
                    <h1 class="mb-3 display-5 fw-bold"><?= htmlspecialchars($motor['name']) ?></h1>
                    <p class="lead mb-2">Prijs: <span class="fw-bold">&euro;<?= number_format($motor['price'], 2, ',', '.') ?></span></p>
                    <p class="mb-4"><?= nl2br(htmlspecialchars($motor['description'])) ?></p>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-lg-7">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Reviews</h3>
                        <form method="post" class="d-flex align-items-center gap-2">
                            <label for="sort" class="form-label mb-0 me-2">Sorteren:</label>
                            <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Nieuwste eerst</option>
                                <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oudste eerst</option>
                                <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Hoogste cijfer</option>
                                <option value="rating_asc" <?= $sort === 'rating_asc' ? 'selected' : '' ?>>Laagste cijfer</option>
                            </select>
                        </form>
                    </div>
                    <?php if (count($reviews) === 0): ?>
                        <p class="text-muted">Er zijn nog geen reviews voor deze motor.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="card review-card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?= htmlspecialchars($review['naam']) ?></strong>
                                        <span class="text-warning">
                                            <?php for ($s = 0; $s < $review['beoordeling']; $s++): ?>
                                                <i class="bi bi-star-fill"></i>
                                            <?php endfor; ?>
                                            <?php for ($s = $review['beoordeling']; $s < 5; $s++): ?>
                                                <i class="bi bi-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <p class="mb-1">"<?= nl2br(htmlspecialchars($review['bericht'])) ?>"</p>
                                    <small class="text-muted"><?= date('d-m-Y H:i', strtotime($review['datum'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5">
                    <div class="review-form mt-4 mt-lg-0">
                        <h4 class="mb-3">Plaats een review</h4>
                        <?php if ($reviewSuccess): ?>
                            <div class="alert alert-success">Bedankt voor je review!</div>
                        <?php elseif ($reviewError): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($reviewError) ?></div>
                        <?php endif; ?>
                        <form method="post" class="mt-3">
                            <div class="mb-3">
                                <label for="naam" class="form-label">Naam</label>
                                <input type="text" class="form-control" id="naam" name="naam" required>
                            </div>
                            <div class="mb-3">
                                <label for="beoordeling" class="form-label">Beoordeling</label>
                                <select class="form-select" id="beoordeling" name="beoordeling" required>
                                    <option value="">Kies aantal sterren</option>
                                    <option value="5">5 sterren</option>
                                    <option value="4">4 sterren</option>
                                    <option value="3">3 sterren</option>
                                    <option value="2">2 sterren</option>
                                    <option value="1">1 ster</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="bericht" class="form-label">Bericht</label>
                                <textarea class="form-control" id="bericht" name="bericht" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Review plaatsen</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-12 d-flex justify-content-center justify-content-lg-start">
                    <a href="index.php#motors" class="btn btn-warning btn-lg px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-arrow-left-circle me-2"></i>Terug naar overzicht
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Motor niet gevonden.</div>
            <a href="index.php#motors" class="btn btn-warning btn-lg mt-3"><i class="bi bi-arrow-left-circle me-2"></i>Terug naar overzicht</a>
        <?php endif; ?>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 