<?php
session_start();
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['naam'], $_POST['beoordeling'], $_POST['bericht']) && !isset($_POST['like_review_id'])) {
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
$stmt = $db->prepare("SELECT id, naam, beoordeling, bericht, datum FROM reviews WHERE motor_id = ? ORDER BY $orderBy");
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
        .like-btn, .dislike-btn {
            border: none;
            background: none;
            color: #333;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .like-btn.liked, .dislike-btn.disliked {
            color: #0d6efd;
        }
        .dislike-btn {
            color: #dc3545;
        }
        .dislike-btn.disliked {
            color: #b02a37;
        }
    </style>
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
                        try {
                            $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
                            $stmt->execute([$_SESSION['user_id']]);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($row && $row['role'] === 'admin') {
                                $isAdmin = true;
                            }
                        } catch (Exception $e) {}
                        ?>
                        <li class="nav-item">
                            <span class="nav-link disabled">
                                Hallo, <?= htmlspecialchars($_SESSION['username']) ?>
                                <?php if ($isAdmin): ?>
                                    <span class="badge bg-danger ms-2">Admin</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <?php if ($isAdmin): ?>
                            <li class="nav-item"><a class="nav-link text-danger fw-bold" href="admin_orders.php"><i class="bi bi-clipboard-data"></i> Admin Orders</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Log in</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Registreren</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="bi bi-cart3 fs-4"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">0</span>
                        </a>
                    </li>
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
                    <button class="btn btn-warning btn-lg add-to-cart-btn mb-3" data-id="<?= $motor['id'] ?>" data-name="<?= htmlspecialchars($motor['name']) ?>" data-price="<?= $motor['price'] ?>" data-img="<?= htmlspecialchars($imgSrc) ?>">
                        <i class="bi bi-cart-plus"></i> Toevoegen aan winkelmandje
                    </button>
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
                                    <div class="mt-2 d-flex align-items-center gap-3">
                                        <button type="button" class="like-btn" data-review="<?= $review['id'] ?>" data-type="like" <?= !isset($_SESSION['user_id']) ? 'disabled' : '' ?>><i class="bi bi-hand-thumbs-up"></i> <span class="like-count" id="like-count-<?= $review['id'] ?>">0</span></button>
                                    </div>
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
    <!-- Voeg winkelmandje modal toe -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cartModalLabel"><i class="bi bi-cart3 me-2"></i>Winkelmandje</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="cart-items" class="row g-3"></div>
            <div id="cart-empty" class="text-center text-muted">Je winkelmandje is leeg.</div>
            <div id="cart-total-row" class="mt-4 d-none">
                <div class="d-flex justify-content-end align-items-center">
                    <span class="fw-bold fs-5 me-2">Totaal:</span>
                    <span class="fw-bold fs-4" id="cart-total">&euro;0,00</span>
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
            <button type="button" class="btn btn-primary" id="checkout-btn" disabled>Bestellen</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Voeg winkelmandje knop toe bij de motor -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Like met localStorage
    document.addEventListener('DOMContentLoaded', function() {
        function getReviewVotes() {
            return JSON.parse(localStorage.getItem('reviewVotes') || '{}');
        }
        function setReviewVotes(votes) {
            localStorage.setItem('reviewVotes', JSON.stringify(votes));
        }
        function updateCounts() {
            const votes = getReviewVotes();
            document.querySelectorAll('.like-btn').forEach(btn => {
                const reviewId = btn.getAttribute('data-review');
                const count = votes[reviewId]?.like || 0;
                document.getElementById('like-count-' + reviewId).textContent = count;
            });
        }
        updateCounts();
        <?php if (isset($_SESSION['user_id'])): ?>
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review');
                let votes = getReviewVotes();
                if (!votes[reviewId]) votes[reviewId] = {like:0, voted:null};
                if (votes[reviewId].voted === 'like') return; // al geliked
                votes[reviewId].like++;
                votes[reviewId].voted = 'like';
                setReviewVotes(votes);
                updateCounts();
            });
        });
        <?php endif; ?>
    });
    </script>
    <!-- Voeg winkelmandje JavaScript toe -->
    <script>
function getCart() {
    return JSON.parse(localStorage.getItem('cart') || '[]');
}
function setCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
}
function updateCartCount() {
    const cart = getCart();
    let count = 0;
    cart.forEach(item => { count += item.aantal || 1; });
    document.getElementById('cart-count').textContent = count;
}
function renderCart() {
    const cart = getCart();
    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    const cartTotalRow = document.getElementById('cart-total-row');
    const cartTotal = document.getElementById('cart-total');
    cartItems.innerHTML = '';
    if (cart.length === 0) {
        cartEmpty.style.display = '';
        cartTotalRow.classList.add('d-none');
        document.getElementById('checkout-btn').disabled = true;
        return;
    }
    cartEmpty.style.display = 'none';
    cartTotalRow.classList.remove('d-none');
    document.getElementById('checkout-btn').disabled = false;
    let total = 0;
    cart.forEach(item => {
        total += item.price * (item.aantal || 1);
        const div = document.createElement('div');
        div.className = 'col-12';
        div.innerHTML = `
            <div class=\"card mb-2 shadow-sm\">
                <div class=\"row g-0 align-items-center\">
                    <div class=\"col-2\"><img src=\"${item.img}\" class=\"img-fluid rounded\" style=\"max-height:60px;\"></div>
                    <div class=\"col-6\">
                        <div class=\"fw-bold\">${item.name}</div>
                        <div class=\"text-muted\">&euro;${item.price.toLocaleString('nl-NL', {minimumFractionDigits:2})} x ${item.aantal || 1}</div>
                    </div>
                    <div class=\"col-2\">
                        <button class=\"btn btn-sm btn-outline-danger remove-from-cart-btn\" data-id=\"${item.id}\"><i class=\"bi bi-trash\"></i></button>
                    </div>
                </div>
            </div>
        `;
        cartItems.appendChild(div);
    });
    cartTotal.textContent = `€${total.toLocaleString('nl-NL', {minimumFractionDigits:2})}`;
    // Verwijderknoppen
    document.querySelectorAll('.remove-from-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            let cart = getCart();
            cart = cart.filter(item => item.id != id);
            setCart(cart);
            updateCartCount();
            renderCart();
        });
    });
}
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    renderCart();
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = parseFloat(this.getAttribute('data-price'));
            const img = this.getAttribute('data-img');
            let cart = getCart();
            let found = cart.find(item => item.id == id);
            if (found) {
                found.aantal = (found.aantal || 1) + 1;
            } else {
                cart.push({id, name, price, img, aantal: 1});
            }
            setCart(cart);
            updateCartCount();
            renderCart();
        });
    });
    document.getElementById('cartModal').addEventListener('show.bs.modal', renderCart);
});
document.getElementById('checkout-btn').addEventListener('click', function() {
    const cart = getCart();
    if (cart.length === 0) return;
    fetch('order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cart})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            setCart([]);
            updateCartCount();
            renderCart();
            alert('Bestelling geplaatst!');
            var modal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
            if (modal) modal.hide();
        } else {
            alert(data.message || 'Er is iets misgegaan.');
        }
    })
    .catch(() => alert('Er is iets misgegaan bij het plaatsen van de bestelling.'));
});
</script>
</body>
</html> 