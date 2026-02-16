# Backoffice Symfony (MVC + Workflow + GED)

Cette version reconstruit l'application en **Symfony** avec :
- **MVC Symfony** (controllers + Twig)
- **Authentification Symfony Security** (form_login)
- **Moteur de workflow via Symfony Workflow**
- **GED** avec upload/consultation de pièces jointes + configuration VichUploader/Flysystem
- **Notifications email** via Symfony Mailer
- **Import automatique des sollicitations JSON** déposées sur le serveur

## Stack Symfony utilisée

- `symfony/framework-bundle`
- `symfony/security-bundle`
- `symfony/workflow`
- `symfony/twig-bundle`
- `doctrine/orm` + `doctrine/doctrine-bundle`
- `symfony/mailer`
- `vich/uploader-bundle` (GED)
- `league/flysystem-bundle` (stockage fichiers)

## Fonctionnel implémenté

1. **Login/password** (`/login`) avec guard Symfony Security.
2. **Dashboard** (`/`) listant les sollicitations assignées à l'utilisateur connecté.
3. **Détail dossier** (`/solicitation/{id}`) avec :
   - statut,
   - transitions workflow,
   - réattribution à un autre acteur,
   - historique,
   - upload / ouverture des PJ.
4. **Workflow Symfony** `solicitation` (places : `recu`, `pris_en_compte`, `en_cours`, `terminee`).
5. **Notifications email**:
   - vers l'acteur réassigné,
   - vers le demandeur à la clôture.
6. **Import JSON** depuis `storage/incoming/*.json`.

## Structure

- `src/Controller/*` : MVC controllers
- `src/Entity/*` : modèle Doctrine
- `src/Service/*` : import JSON + notifications
- `config/packages/*` : Security, Workflow, Doctrine, Mailer, GED
- `templates/*` : vues Twig
- `data/incoming-samples/*` : exemples JSON

## Installation

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console app:bootstrap
php -S 0.0.0.0:8000 -t public
```

Puis ouvrir `http://localhost:8000`.

Comptes de démonstration :
- `agent1 / agent1`
- `manager1 / manager1`
- `legal1 / legal1`

## Notes importantes (environnement actuel)

Dans cet environnement d’exécution, l’accès réseau à Packagist est bloqué (403), donc `composer install` ne peut pas être exécuté ici.
Le code Symfony est livré/restructuré, mais l’installation des dépendances doit être lancée sur votre environnement CI/local avec accès internet.

## Dépannage `cache:clear`

Si vous voyez l'erreur :
`Unrecognized option "with_constructor_extractor" under "framework.property_info"`,
supprimez cette option et gardez uniquement :

```yaml
framework:
  property_info:
    enabled: true
```

Cette version du projet est alignée avec cette configuration.

