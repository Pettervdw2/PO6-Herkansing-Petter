<?php
session_start();
require_once 'dbconnect.php';

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bericht = trim($_POST['bericht'] ?? '');
    if ($naam && $email && $bericht) {
        $stmt = $db->prepare('INSERT INTO contact (naam, email, bericht, datum) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$naam, $email, $bericht]);
        $_SESSION['contact_success'] = true;
        header('Location: contact.php');
        exit;
    } else {
        $error = 'Vul alle velden in aub.';
    }
}
$showSuccess = false;
if (isset($_SESSION['contact_success'])) {
    $showSuccess = true;
    unset($_SESSION['contact_success']);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Contact</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
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
                        <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                        <?php if ($isAdmin): ?>
                            <li class="nav-item"><a class="nav-link text-danger fw-bold" href="admin_orders.php"><i class="bi bi-clipboard-data"></i> Admin Orders</a></li>
                        <?php endif; ?>
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
    <div class="container mt-5">
        <h1>Contact</h1>
        <?php if ($showSuccess): ?>
            <div class="alert alert-success">Je bericht is succesvol verstuurd en we zullen binnen 24 uur contact met je opnemen.</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form class="mt-4" method="post">
            <div class="mb-3">
                <label for="naam" class="form-label">Naam</label>
                <input type="text" class="form-control" id="naam" name="naam" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="bericht" class="form-label">Bericht</label>
                <textarea class="form-control" id="bericht" name="bericht" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Verzenden</button>
        </form>
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
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
            <button type="button" class="btn btn-primary" id="checkout-btn" disabled>Bestellen</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function getCart() {
    return JSON.parse(localStorage.getItem('cart') || '[]');
}
function updateCartCount() {
    const cart = getCart();
    document.getElementById('cart-count').textContent = cart.length;
}
function renderCart() {
    const cart = getCart();
    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    cartItems.innerHTML = '';
    if (cart.length === 0) {
        cartEmpty.style.display = '';
        document.getElementById('checkout-btn').disabled = true;
        return;
    }
    cartEmpty.style.display = 'none';
    document.getElementById('checkout-btn').disabled = false;
    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'col-12';
        div.innerHTML = `
            <div class="card mb-2 shadow-sm">
                <div class="row g-0 align-items-center">
                    <div class="col-2"><img src="${item.img}" class="img-fluid rounded" style="max-height:60px;"></div>
                    <div class="col-6">
                        <div class="fw-bold">${item.name}</div>
                        <div class="text-muted">&euro;${item.price.toLocaleString('nl-NL', {minimumFractionDigits:2})}</div>
                    </div>
                </div>
            </div>
        `;
        cartItems.appendChild(div);
    });
}
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    renderCart();
    document.getElementById('cartModal').addEventListener('show.bs.modal', renderCart);
});
</script>
</body>
</html> 