<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/TrackerRepository.php';

$repo = new TrackerRepository(db());
$from = $_GET['from'] ?? gmdate('Y-m-d\TH:i', strtotime('-24 hours'));
$to = $_GET['to'] ?? gmdate('Y-m-d\TH:i');

$fromIso = $from ? gmdate('c', strtotime($from)) : null;
$toIso = $to ? gmdate('c', strtotime($to)) : null;

$positions = $repo->between($fromIso, $toIso);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traceur LifeTag</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f7f9; }
        main { max-width: 1100px; margin: 1rem auto; padding: 1rem; }
        .card { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        form { display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; }
        label { font-size: 0.9rem; color: #333; display: flex; flex-direction: column; gap: 0.3rem; }
        button { background: #2f6fed; color: #fff; border: 0; padding: 0.6rem 1rem; border-radius: 8px; cursor: pointer; }
        #map { height: 540px; margin-top: 1rem; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        th, td { padding: 0.4rem; border-bottom: 1px solid #ececec; text-align: left; }
    </style>
</head>
<body>
<main>
    <h1>Historique GPS — LifeTag</h1>
    <div class="card">
        <form method="get">
            <label>Du
                <input type="datetime-local" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES) ?>">
            </label>
            <label>Au
                <input type="datetime-local" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES) ?>">
            </label>
            <button type="submit">Filtrer</button>
        </form>

        <div id="map"></div>
        <table>
            <thead><tr><th>Date</th><th>Latitude</th><th>Longitude</th><th>Précision (m)</th></tr></thead>
            <tbody>
            <?php foreach ($positions as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['captured_at'], ENT_QUOTES) ?></td>
                    <td><?= number_format((float)$p['latitude'], 6, '.', ' ') ?></td>
                    <td><?= number_format((float)$p['longitude'], 6, '.', ' ') ?></td>
                    <td><?= $p['accuracy'] !== null ? number_format((float)$p['accuracy'], 1, '.', ' ') : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const data = <?= json_encode($positions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const map = L.map('map');
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

if (data.length === 0) {
  map.setView([48.8566, 2.3522], 11);
} else {
  const points = data.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
  const polyline = L.polyline(points, {color: '#2f6fed'}).addTo(map);
  points.forEach((pt, idx) => {
    L.circleMarker(pt, {radius: idx === points.length - 1 ? 8 : 5}).addTo(map)
      .bindPopup(`${data[idx].captured_at}<br>${pt[0].toFixed(6)}, ${pt[1].toFixed(6)}`);
  });
  map.fitBounds(polyline.getBounds().pad(0.2));
}
</script>
</body>
</html>
