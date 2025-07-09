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

// Admin check
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['role'] === 'admin') {
        $isAdmin = true;
    }
}

// Motor toevoegen (admin)
$addSuccess = false;
$addError = '';
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_motor'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $imgPath = '';
    if ($name && $description && $price > 0) {
        // Afbeelding uploaden
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed)) {
                $imgPath = 'img/' . uniqid('motor_', true) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/' . $imgPath);
            } else {
                $addError = 'Alleen jpg, jpeg, png, gif toegestaan.';
            }
        }
        if (!$addError) {
            $stmt = $db->prepare('INSERT INTO motors (name, description, price) VALUES (?, ?, ?)');
            $stmt->execute([$name, $description, $price]);
            $newId = $db->lastInsertId();
            if ($imgPath) {
                // Sla pad op in images-array
                $images[$newId] = $imgPath;
            }
            $addSuccess = true;
            // Refresh motors
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $motors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $addError = 'Vul alle velden correct in.';
    }
}

// Motor verwijderen (admin)
if ($isAdmin && isset($_POST['delete_motor_id'])) {
    $deleteId = (int)$_POST['delete_motor_id'];
    // Verwijder afbeelding indien aanwezig
    if (isset($images[$deleteId]) && file_exists(__DIR__ . '/' . $images[$deleteId])) {
        unlink(__DIR__ . '/' . $images[$deleteId]);
    }
    $stmt = $db->prepare('DELETE FROM motors WHERE id = ?');
    $stmt->execute([$deleteId]);
    // Refresh motors
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $motors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
    <div class="container mt-4">
        <h1 class="mb-4"></h1>
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
                                <button class="btn btn-warning add-to-cart-btn mb-2 w-100" data-id="<?= $motor['id'] ?>" data-name="<?= htmlspecialchars($motor['name']) ?>" data-price="<?= $motor['price'] ?>" data-img="<?= htmlspecialchars($imgSrc) ?>">
                                    <i class="bi bi-cart-plus"></i> Toevoegen aan winkelmandje
                                </button>
                                <a href="detail.php?id=<?= urlencode($motor['id']) ?>" class="btn btn-primary w-100 mb-2">Bekijk meer</a>
                                <?php if ($isAdmin): ?>
                                    <form method="post" onsubmit="return confirm('Weet je zeker dat je deze motor wilt verwijderen?');">
                                        <input type="hidden" name="delete_motor_id" value="<?= $motor['id'] ?>">
                                        <button type="submit" class="btn btn-danger w-100"><i class="bi bi-trash"></i> Verwijderen</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php if ($isAdmin): ?>
        <div class="card mb-4 mt-5">
            <div class="card-body">
                <h5 class="card-title mb-3">Nieuwe motor toevoegen</h5>
                <?php if ($addSuccess): ?><div class="alert alert-success">Motor toegevoegd!</div><?php endif; ?>
                <?php if ($addError): ?><div class="alert alert-danger"><?= htmlspecialchars($addError) ?></div><?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Naam</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prijs (&euro;)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Afbeelding</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschrijving</label>
                            <textarea class="form-control" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_motor" class="btn btn-success mt-3">Toevoegen</button>
                </form>
            </div>
        </div>
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
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                <div class="card mb-2 shadow-sm">
                    <div class="row g-0 align-items-center">
                        <div class="col-2"><img src="${item.img}" class="img-fluid rounded" style="max-height:60px;"></div>
                        <div class="col-6">
                            <div class="fw-bold">${item.name}</div>
                            <div class="text-muted">&euro;${item.price.toLocaleString('nl-NL', {minimumFractionDigits:2})} x ${item.aantal || 1}</div>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-sm btn-outline-danger remove-from-cart-btn" data-id="${item.id}"><i class="bi bi-trash"></i></button>
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