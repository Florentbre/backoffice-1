# LifeTag GPS Tracker Web App (PHP + SQLite)

Application web qui :

1. Interroge un service distant toutes les 5 minutes pour récupérer la position GPS d'un traceur type LifeTag.
2. Stocke l'historique dans SQLite.
3. Affiche les positions sur carte (Leaflet/OpenStreetMap) avec filtre entre 2 dates.

## Connecteur Google "Localiser mon appareil" (non officiel)

Tu voulais une connexion directe aux services Google : c'est pris en charge via `LOCATOR_PROVIDER=google_unofficial`.

Le connecteur `GoogleFindMyDeviceClient` envoie une requête HTTP vers Google (`GOOGLE_FMD_URL`) avec paramètres configurables :
- méthode,
- bearer token,
- cookie de session,
- body JSON.

> Important : ce flux est non officiel et peut casser à tout moment si Google modifie ses endpoints ou son modèle d'authentification.

## Prérequis

- PHP 8.2+
- Extension PDO SQLite activée

## Installation

```bash
cp .env.example .env
```

Puis adapter `.env` selon ton mode :
- `google_unofficial` (direct Google),
- `generic` (endpoint proxy maison).

## Lancer l'application web

```bash
php -S 0.0.0.0:8000 -t public
```

Ouvrir http://localhost:8000.

## Lancer la collecte ponctuelle

```bash
set -a && source .env && set +a
php scripts/poll.php
```

## Planification toutes les 5 minutes (cron)

```cron
*/5 * * * * cd /workspace/backoffice-1 && /usr/bin/env bash -lc 'set -a && source .env && set +a && php scripts/poll.php >> data/poller.log 2>&1'
```

## Parsing JSON

Le poller cherche dans la réponse JSON les champs (même imbriqués):
- latitude: `latitude` ou `lat`
- longitude: `longitude`, `lng` ou `lon`
- précision (optionnelle): `accuracy`, `horizontalAccuracy` ou `radius`
