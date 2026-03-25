# LifeTag GPS Tracker Web App (PHP + SQLite)

Application web minimaliste qui :

1. Interroge un service distant toutes les 5 minutes pour récupérer la position GPS d'un traceur type LifeTag.
2. Stocke l'historique dans SQLite.
3. Affiche les positions sur carte (Leaflet/OpenStreetMap) avec filtre entre 2 dates.

## ⚠️ À propos de Google "Localiser mon appareil"

Google ne fournit pas d'API publique officielle documentée pour récupérer directement la position de tous les appareils/traceurs depuis des scripts tiers.

Cette application est donc construite avec un **endpoint compatible** (`LOCATOR_ENDPOINT_URL`) :
- soit un proxy interne que vous maîtrisez,
- soit un connecteur maison qui récupère la donnée autorisée et renvoie un JSON.

## Prérequis

- PHP 8.2+
- Extension PDO SQLite activée

## Installation

```bash
cp .env.example .env
```

Puis adaptez `.env`.

## Lancer l'application web

```bash
set -a && source .env && set +a
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

## Format JSON accepté

Le poller cherche dans la réponse JSON les champs (même imbriqués):
- latitude: `latitude` ou `lat`
- longitude: `longitude`, `lng` ou `lon`
- précision (optionnelle): `accuracy`, `horizontalAccuracy` ou `radius`

Exemple :

```json
{
  "device": {
    "position": {
      "lat": 48.8566,
      "lng": 2.3522,
      "accuracy": 12.4
    }
  }
}
```
