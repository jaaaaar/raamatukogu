<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "db.php";
require_once "functions.php";

$veateade = '';
$edukalt = false;

$raamatudResult = $mysqli->query("SELECT RaamatID, Pealkiri, Autor FROM raamat ORDER BY Pealkiri");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eesnimi = trim($_POST['eesnimi'] ?? '');
    $perekonnanimi = trim($_POST['perekonnanimi'] ?? '');
    $isikukood = trim($_POST['isikukood'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $raamatId = intval($_POST['raamat']);

    if (!$eesnimi || !$perekonnanimi || !$isikukood || !$email || !$raamatId) {
        $veateade = "Palun täida kõik väljad.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $veateade = "Sisesta korrektne e-posti aadress.";
    } elseif (strlen($isikukood) !== 11 || !ctype_digit($isikukood)) {
        $veateade = "Isikukood peab olema 11 numbrit.";
    } else {
        // Kontroll kasutaja olemasolu
        $stmt = $mysqli->prepare("SELECT KasutajaID FROM kasutaja WHERE Isikukood = ?");
        $stmt->bind_param("s", $Isikukood);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rida = $result->fetch_assoc();
            $kasutajaId = $rida['KasutajaID'];
        } else {
            $parool = password_hash('vaikimisi', PASSWORD_DEFAULT);
            $stmt2 = $mysqli->prepare("INSERT INTO Kasutaja (Eesnimi, Perekonnanimi, Isikukood, Email, Roll, Parool) VALUES (?, ?, ?, ?, 'kylastaja', ?)");
            $stmt2->bind_param("sssss", $Eesnimi, $Perekonnanimi, $Isikukood, $Email, $Parool);
            $stmt2->execute();
            $KasutajaID = $stmt2->insert_id;
        }

        // Kontrollime vabu eksemplare
        $vabadEksemplarid = saadaVabadEksemplarid($RaamatID);
        if ($vabadEksemplarid === 0) {
            $veateade = "Raamat ei ole hetkel saadaval.";
        } else {
            // Kontroll tagastamata raamatute kohta
            $kontroll = $mysqli->prepare("SELECT COUNT(*) AS arv FROM Laenutus WHERE KasutajaID = ? AND Tagastatud = FALSE");
            $kontroll->bind_param("i", $kasutajaId);
            $kontroll->execute();
            $tagastamata = $kontroll->get_result()->fetch_assoc()['arv'];

            if ($tagastamata > 0) {
                $veateade = "Teil on veel tagastamata raamatuid, broneerimine ei ole lubatud.";
            } else {
                // Otsime esimese vaba eksemplari ID
                $stmt3 = $mysqli->prepare("SELECT EksemplarID FROM Eksemplar WHERE RaamatID = ? AND Staatus = 'vaba' LIMIT 1");
                $stmt3->bind_param("i", $raamatId);
                $stmt3->execute();
                $eksemplar = $stmt3->get_result()->fetch_assoc();
                $eksemplarId = $eksemplar['EksemplarID'];

                // Lisame laenutuse
                $algus = date('Y-m-d H:i:s');
                $lopp = date('Y-m-d H:i:s', strtotime('+14 days'));

                $mysqli->begin_transaction();
                try {
                    $laenutusInsert = $mysqli->prepare("INSERT INTO Laenutus (KasutajaID, EksemplarID, LaenutusAlgus, LaenutusLopp, Tagastatud) VALUES (?, ?, ?, ?, FALSE)");
                    $laenutusInsert->bind_param("iiss", $kasutajaId, $eksemplarId, $algus, $lopp);
                    $laenutusInsert->execute();

                    uuendaEksemplariStaatus($eksemplarId, 'laenutatud');

                    // Vähendame ka raamatute vaba eksemplaride arvu tabelis
                    $updateRaamat = $mysqli->prepare("UPDATE Raamat SET EksemplarideArv = EksemplarideArv - 1 WHERE RaamatID = ?");
                    $updateRaamat->bind_param("i", $raamatId);
                    $updateRaamat->execute();

                    $mysqli->commit();
                    $edukalt = true;
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $veateade = "Laenutamisel tekkis viga: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Raamatukogu - Raamatu laenutus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('raamatukogu.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
        }
        .form-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .book-icon {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-book-open me-2"></i>Raamatukogu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Laenutus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Raamatud</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Kontakt</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Raamatukogu hullus</h1>
            <p class="lead">Eriti nobedatele raamatu inimestele</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <?php if ($edukalt): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Edukalt laenutatud!</h5>
                            <p class="mb-0">Raamat on edukalt laenutatud! Palun tagasta see hiljem kui oled lugemise lõpetanud.</p>
                        </div>
                    </div>
                    <?php elseif ($veateade): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?= htmlspecialchars($veateade) ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <div class="book-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h2>Laenutavorm</h2>
                        <p class="text-muted">Sisesta andmed raamatu laenutamiseks</p>
                    </div>

                    <form method="post" novalidate class="needs-validation">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="eesnimi" class="form-label">Eesnimi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="eesnimi" name="eesnimi" required value="<?= htmlspecialchars($_POST['eesnimi'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="perekonnanimi" class="form-label">Perekonnanimi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="perekonnanimi" name="perekonnanimi" required value="<?= htmlspecialchars($_POST['perekonnanimi'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="isikukood" class="form-label">Isikukood</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="isikukood" name="isikukood" required minlength="11" maxlength="11" pattern="\d{11}" value="<?= htmlspecialchars($_POST['isikukood'] ?? '') ?>">
                                </div>
                                <div class="form-text">11 numbrit</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-post</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="raamat" class="form-label">Raamat</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-book"></i></span>
                                    <select class="form-select" id="raamat" name="raamat" required>
                                        <option value="">Vali raamat...</option>
                                        <?php while($rida = $raamatudResult->fetch_assoc()): ?>
                                            <option value="<?= $rida['RaamatID'] ?>" <?= (isset($_POST['raamat']) && $_POST['raamat'] == $rida['RaamatID']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rida['Pealkiri'] . " - " . $rida['Autor']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-bookmark me-2"></i>Laenuta raamat
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; 2023 Raamatukogu. Kõik õigused kaitstud.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict'
            
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
