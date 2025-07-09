<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Haal gebruikersgegevens op
$stmt = $db->prepare('SELECT username, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$pwSuccess = false;
$pwError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $new_password2 = $_POST['new_password2'] ?? '';
    if ($new_password && $new_password === $new_password2 && strlen($new_password) >= 6) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user_id]);
        $pwSuccess = true;
    } else {
        $pwError = 'Wachtwoorden komen niet overeen of zijn te kort (minimaal 6 tekens).';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Profiel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#motors">Motors</a></li>
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
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="mb-4">Mijn Profiel</h1>
        <div class="card mb-4" style="max-width: 500px;">
            <div class="card-body">
                <h5 class="card-title mb-3">Gebruikersgegevens</h5>
                <p class="mb-1"><strong>Gebruikersnaam:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p class="mb-1"><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>
        <div class="card" style="max-width: 500px;">
            <div class="card-body">
                <h5 class="card-title mb-3">Wachtwoord wijzigen</h5>
                <?php if ($pwSuccess): ?>
                    <div class="alert alert-success">Wachtwoord succesvol gewijzigd!</div>
                <?php elseif ($pwError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($pwError) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nieuw wachtwoord</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="new_password2" class="form-label">Herhaal nieuw wachtwoord</label>
                        <input type="password" class="form-control" id="new_password2" name="new_password2" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 