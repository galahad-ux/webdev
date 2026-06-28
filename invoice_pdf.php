<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id    = (int)$_SESSION['user_id'];
$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$invoice_id) {
    header('Location: user_page');
    exit();
}

// Fetch invoice — must belong to current user
$stmt = $pdo->prepare("
    SELECT inv.*,
           tb.num_participants, tb.status AS booking_status,
           td.departure_date, td.return_date, td.duration_nights,
           COALESCE(tpt.name, tpt_fb.name) AS package_name,
           COALESCE(tpt.highlights, tpt_fb.highlights) AS highlights,
           dt.name AS city_name,
           u.name AS user_name, u.email AS user_email,
           ag.company_name AS agency_name
    FROM invoice inv
    JOIN trip_booking tb ON inv.booking_id = tb.booking_id
    JOIN trip_date td ON tb.date_id = td.date_id
    JOIN trip_package tp ON td.package_id = tp.package_id
    LEFT JOIN trip_package_translation tpt ON tp.package_id = tpt.package_id AND tpt.language_code = 'fr'
    LEFT JOIN trip_package_translation tpt_fb ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'en'
    LEFT JOIN destination_translation dt ON tp.destination_id = dt.destination_id AND dt.language_code = 'fr'
    JOIN user u ON inv.user_id = u.user_id
    JOIN agency ag ON tp.agency_id = ag.agency_id
    WHERE inv.invoice_id = ? AND inv.user_id = ?
");
$stmt->execute([$invoice_id, $user_id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    header('Location: user_page');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?> — Momo Travel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Georgia', serif; color: #333; background: #fff; font-size: 14px; }
        .page { max-width: 760px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #c1272d; padding-bottom: 24px; margin-bottom: 32px; }
        .logo { font-family: 'Georgia', serif; font-size: 2.5rem; color: #c1272d; font-style: italic; letter-spacing: -1px; }
        .invoice-label { text-align: right; }
        .invoice-label h2 { font-size: 1.4rem; color: #4a1c35; }
        .invoice-label p { color: #888; font-size: 0.9rem; margin-top: 4px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 32px; gap: 2rem; }
        .party { flex: 1; }
        .party h4 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 8px; }
        .party p { font-size: 0.95rem; line-height: 1.6; }
        .trip-section { background: #f9f9f9; border-radius: 8px; padding: 20px 24px; margin-bottom: 28px; }
        .trip-section h3 { font-size: 1.1rem; color: #c1272d; margin-bottom: 12px; }
        .trip-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .trip-row:last-child { border-bottom: none; }
        .trip-row span:first-child { color: #666; }
        .trip-row span:last-child { font-weight: 600; }
        .total-section { border-top: 2px solid #c1272d; padding-top: 20px; margin-bottom: 32px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.95rem; }
        .total-final { font-size: 1.3rem; font-weight: bold; color: #c1272d; border-top: 1px solid #eee; padding-top: 12px; margin-top: 8px; }
        .total-final span:first-child { color: #333; font-weight: bold; }
        .payment-info { background: #e6f4ea; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px; font-size: 0.9rem; }
        .payment-info h4 { color: #1e8e3e; margin-bottom: 8px; }
        .footer { text-align: center; font-size: 0.8rem; color: #aaa; border-top: 1px solid #eee; padding-top: 20px; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #c1272d; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; font-weight: bold; z-index: 100; }
        .print-btn:hover { background: #a01f24; }
        @media print {
            .print-btn { display: none; }
            body { font-size: 12px; }
            .page { padding: 20px; }
        }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Imprimer / PDF</button>

<div class="page">

    <div class="header">
        <div class="logo">momo</div>
        <div class="invoice-label">
            <h2>FACTURE / INVOICE</h2>
            <p><?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Date : <?= date('d/m/Y', strtotime($inv['payment_date'])) ?></p>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h4>Émetteur / Issued by</h4>
            <p>
                <strong>Momo Travel</strong><br>
                <?= htmlspecialchars($inv['agency_name'], ENT_QUOTES, 'UTF-8') ?><br>
                contact@momo-travel.com
            </p>
        </div>
        <div class="party" style="text-align:right;">
            <h4>Client</h4>
            <p>
                <strong><?= htmlspecialchars($inv['user_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                <?= htmlspecialchars($inv['user_email'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    </div>

    <div class="trip-section">
        <h3>Détails du voyage / Trip details</h3>
        <div class="trip-row">
            <span>Voyage / Trip</span>
            <span><?= htmlspecialchars($inv['package_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="trip-row">
            <span>Destination</span>
            <span><?= htmlspecialchars($inv['city_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="trip-row">
            <span>Départ / Departure</span>
            <span><?= date('d/m/Y', strtotime($inv['departure_date'])) ?></span>
        </div>
        <div class="trip-row">
            <span>Retour / Return</span>
            <span><?= date('d/m/Y', strtotime($inv['return_date'])) ?></span>
        </div>
        <div class="trip-row">
            <span>Durée / Duration</span>
            <span><?= (int)$inv['duration_nights'] ?> nuits / nights</span>
        </div>
        <div class="trip-row">
            <span>Voyageurs / Travelers</span>
            <span><?= (int)$inv['num_participants'] ?></span>
        </div>
        <?php if ($inv['highlights']): ?>
        <div class="trip-row" style="flex-direction:column; gap:4px;">
            <span>Inclus / Included</span>
            <span style="font-weight:normal; color:#555; font-size:0.85rem;"><?= htmlspecialchars($inv['highlights'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="total-section">
        <div class="total-row">
            <span><?= (int)$inv['num_participants'] ?> voyageur(s)</span>
            <span>€<?= number_format((float)$inv['amount'], 2, ',', ' ') ?></span>
        </div>
        <div class="total-row total-final">
            <span>TOTAL PAYÉ / TOTAL PAID</span>
            <span>€<?= number_format((float)$inv['amount'], 2, ',', ' ') ?></span>
        </div>
    </div>

    <div class="payment-info">
        <h4>✅ Paiement confirmé / Payment confirmed</h4>
        <p>
            Mode de paiement / Payment method : Carte bancaire
            <?php if ($inv['card_last4']): ?>
                se terminant par / ending in <?= htmlspecialchars($inv['card_last4'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?><br>
            Date : <?= date('d/m/Y à H:i', strtotime($inv['payment_date'])) ?><br>
            Statut : <strong><?= htmlspecialchars(ucfirst($inv['status']), ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>

    <div class="footer">
        <p>Momo Travel — Tous droits réservés / All rights reserved — 2026</p>
        <p>Ce document fait office de justificatif de paiement. / This document serves as proof of payment.</p>
        <p>N° de réservation / Booking ID : <?= (int)$inv['booking_id'] ?></p>
    </div>

</div>
</body>
</html>
