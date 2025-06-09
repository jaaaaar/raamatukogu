<?php
require_once "db.php";

// Tagastab vaba eksemplaride arvu raamatule
function saadaVabadEksemplarid($raamatID) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS arv FROM Eksemplar WHERE RaamatID = ? AND Staatus = 'vaba'");
    $stmt->bind_param("i", $raamatID);
    $stmt->execute();
    $arv = $stmt->get_result()->fetch_assoc()['arv'];
    return (int)$arv;
}

// Muudab eksemplari staatus
function uuendaEksemplariStaatus($eksemplarID, $staatus) {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE Eksemplar SET Staatus = ? WHERE EksemplarID = ?");
    $stmt->bind_param("si", $staatus, $eksemplarID);
    return $stmt->execute();
}
?>
