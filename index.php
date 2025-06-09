<?php
require_once "db.php";
require_once "functions.php";

$veateade = '';
$edukalt = false;

$raamatudResult = $mysqli->query("SELECT RaamatID, Pealkiri, Autor FROM Raamat ORDER BY Pealkiri");

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
        $stmt = $mysqli->prepare("SELECT KasutajaID FROM Kasutaja WHERE Isikukood = ?");
        $stmt->bind_param("s", $isikukood);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rida = $result->fetch_assoc();
            $kasutajaId = $rida['KasutajaID'];
        } else {
            $parool = password_hash('vaikimisi', PASSWORD_DEFAULT);
            $stmt2 = $mysqli->prepare("INSERT INTO Kasutaja (Eesnimi, Perekonnanimi, Isikukood, Email, Roll, Parool) VALUES (?, ?, ?, ?, 'kylastaja', ?)");
            $stmt2->bind_param("sssss", $eesnimi, $perekonnanimi, $isikukood, $email, $parool);
            $stmt2->execute();
            $kasutajaId = $stmt2->insert_id;
        }

        // Kontrollime vabu eksemplare
        $vabadEksemplarid = saadaVabadEksemplarid($raamatId);
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
</head>
<body>
<div class="container py-4">
<h1 class="mb-4">Raamatu laenutus</h1>

<?php if ($edukalt): ?>
<div class="alert alert-success">
Raamat on edukalt laenutatud! Palun tagasta see hiljem kui oled lugemise lõpetanud.
</div>
<?php elseif ($veateade): ?>
<div class="alert alert-danger"><?= htmlspecialchars($veateade) ?></div>
<?php endif; ?>

<form method="post" novalidate>
    <div class="mb-3">
        <label for="eesnimi" class="form-label">Eesnimi</label>
        <input type="text" class="form-control" id="eesnimi" name="eesnimi" required value="<?= htmlspecialchars($_POST['eesnimi'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="perekonnanimi" class="form-label">Perekonnanimi</label>
        <input type="text" class="form-control" id="perekonnanimi" name="perekonnanimi" required value="<?= htmlspecialchars($_POST['perekonnanimi'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="isikukood" class="form-label">Isikukood</label>
        <input type="text" class="form-control" id="isikukood" name="isikukood" required minlength="11" maxlength="11" pattern="\d{11}" value="<?= htmlspecialchars($_POST['isikukood'] ?? '') ?>">
        <div class="form-text">11 numbrit</div>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">E-post</label>
        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="raamat" class="form-label">Raamat</label>
        <select class="form-select" id="raamat" name="raamat" required>
            <option value="">Vali raamat...</option>
            <?php while($rida = $raamatudResult->fetch_assoc()): ?>
                <option value="<?= $rida['RaamatID'] ?>" <?= (isset($_POST['raamat']) && $_POST['raamat'] == $rida['RaamatID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rida['Pealkiri'] . " - " . $rida['Autor']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Laenuta raamat</button>
</form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
