# Backoffice interne de traitement des sollicitations

Application PHP (sans framework) qui fournit un backoffice complet pour traiter des dossiers entrants au format JSON, avec authentification, dashboard, workflow flexible, notifications mail et gestion documentaire (GED légère).

## Fonctionnalités implémentées

- **Connexion login/mot de passe** (utilisateurs internes stockés en base SQLite).
- **Tableau de bord principal** listant les sollicitations de l'utilisateur connecté.
- **Écran détail dossier** avec:
  - statut d'avancement (`recu`, `pris_en_compte`, `en_cours`, `terminee`),
  - historique de workflow,
  - réattribution à un autre acteur.
- **Moteur de workflow flexible**:
  - transitions standards,
  - mode **ad-hoc** pour forcer des étapes non prévues initialement.
- **Notifications mail**:
  - vers l'acteur assigné lors d'une transition,
  - vers le demandeur initial lors de la clôture.
- **GED / pièces jointes**:
  - upload de fichiers sur un dossier,
  - ouverture directe dans le navigateur (`Content-Disposition: inline`, fonctionne nativement pour PDF/images/texte selon le navigateur).
- **Source de données JSON**:
  - ingestion automatique des fichiers JSON déposés dans `storage/incoming/`.

## Modules PHP standards utilisés

- `PDO` + `sqlite` pour la persistance.
- `session` pour l'authentification.
- Upload de fichiers PHP natif (`$_FILES`, `move_uploaded_file`) pour la GED.
- Journalisation des emails (fichier local) avec possibilité de brancher `mail()` / SMTP.

## Arborescence

- `public/index.php` : point d'entrée web + routing simple.
- `src/` : services applicatifs (auth, workflow, notifications, dossiers).
- `storage/incoming/` : fichiers JSON entrants.
- `storage/attachments/` : pièces jointes des dossiers.
- `storage/logs/mails.log` : trace des emails sortants.

## Démarrage local

```bash
php -S 0.0.0.0:8000 -t public
```

Puis ouvrir `http://localhost:8000`.

### Comptes de démonstration

- `agent1 / agent1`
- `manager1 / manager1`
- `legal1 / legal1`

## Flux type

1. Copier un JSON d'exemple depuis `data/incoming-samples/` vers `storage/incoming/` (ou déposer votre propre fichier).
2. Ouvrir le dashboard, le dossier est importé automatiquement.
3. Ouvrir le détail, changer le statut, réattribuer si besoin.
4. Ajouter des pièces jointes.
5. Vérifier les notifications dans `storage/logs/mails.log`.


## Compatibilité PHP

Le code applicatif a été adapté pour rester compatible avec des environnements PHP plus anciens (sans propriétés typées ni promotion de propriétés constructeur), afin d’éviter les erreurs de parsing au démarrage.
