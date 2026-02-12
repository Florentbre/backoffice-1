<?php

declare(strict_types=1);

use App\Auth;
use App\Database;
use App\EmailNotifier;
use App\SolicitationService;
use App\WorkflowEngine;

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/EmailNotifier.php';
require_once __DIR__ . '/../src/WorkflowEngine.php';
require_once __DIR__ . '/../src/SolicitationService.php';

session_start();

$database = new Database(__DIR__ . '/../storage/backoffice.sqlite');
$database->migrate();

$auth = new Auth($database);
$service = new SolicitationService(
    $database,
    new WorkflowEngine(),
    new EmailNotifier(__DIR__ . '/../storage/logs/mails.log'),
    __DIR__ . '/../storage/incoming',
    __DIR__ . '/../storage/attachments'
);
$service->importIncomingJson();

$action = $_GET['action'] ?? 'dashboard';
$error = null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = $auth->login(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
    if ($ok) {
        header('Location: /');
        exit;
    }

    $error = 'Identifiants invalides';
}

if ($action === 'logout') {
    $auth->logout();
    header('Location: /?action=login');
    exit;
}

if (!$auth->isLoggedIn() && $action !== 'login') {
    header('Location: /?action=login');
    exit;
}

$user = $auth->user();

if ($action === 'update_workflow' && $_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    try {
        $service->updateWorkflow(
            (int) $_POST['id'],
            (int) $user['id'],
            (string) $_POST['next_status'],
            ($_POST['assign_to'] ?? '') === '' ? null : (int) $_POST['assign_to'],
            trim((string) ($_POST['note'] ?? '')),
            isset($_POST['adhoc'])
        );
        $_SESSION['flash'] = 'Workflow mis à jour.';
    } catch (Throwable $e) {
        $_SESSION['flash'] = 'Erreur workflow: ' . $e->getMessage();
    }
    header('Location: /?action=detail&id=' . (int) $_POST['id']);
    exit;
}

if ($action === 'upload_attachment' && $_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    try {
        $service->addAttachment((int) $_POST['id'], $_FILES['attachment'] ?? [], (int) $user['id']);
        $_SESSION['flash'] = 'Pièce jointe ajoutée.';
    } catch (Throwable $e) {
        $_SESSION['flash'] = 'Erreur upload: ' . $e->getMessage();
    }
    header('Location: /?action=detail&id=' . (int) $_POST['id']);
    exit;
}

if ($action === 'view_attachment' && $user) {
    $attachmentId = (int) ($_GET['id'] ?? 0);
    $stmt = $database->pdo()->prepare('SELECT * FROM attachments WHERE id = :id');
    $stmt->execute([':id' => $attachmentId]);
    $att = $stmt->fetch();

    if (!$att) {
        http_response_code(404);
        echo 'Fichier introuvable';
        exit;
    }

    $path = __DIR__ . '/../storage/attachments/' . $att['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Fichier manquant';
        exit;
    }

    header('Content-Type: ' . ($att['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . basename((string) $att['original_name']) . '"');
    readfile($path);
    exit;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Sollicitations</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 2rem; background: #f7f9fc;}
        .card {background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,.08);}
        table {width: 100%; border-collapse: collapse;}
        th, td {padding: .6rem; border-bottom: 1px solid #ddd; text-align: left;}
        a.button, button {background:#0d6efd; color:#fff; border:none; padding:.5rem .8rem; border-radius:6px; text-decoration:none; cursor:pointer;}
        .muted {color: #666; font-size: .9rem;}
        .status {padding:.2rem .4rem; border-radius:4px; background:#eef2ff;}
        input, select, textarea {width: 100%; padding: .4rem; margin-bottom: .5rem;}
        .row {display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;}
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
    <div class="card" style="max-width:420px;margin:5rem auto;">
        <h1>Connexion Backoffice</h1>
        <?php if ($error): ?><p style="color:#b00020"><?= esc($error) ?></p><?php endif; ?>
        <form method="post" action="/?action=login">
            <label>Login</label>
            <input name="username" required>
            <label>Mot de passe</label>
            <input type="password" name="password" required>
            <button type="submit">Se connecter</button>
        </form>
        <p class="muted">Comptes de démo: agent1/agent1, manager1/manager1, legal1/legal1</p>
    </div>
<?php else: ?>
    <div class="card">
        <strong>Connecté:</strong> <?= esc((string) $user['username']) ?> (<?= esc((string) $user['service']) ?>)
        <a class="button" style="float:right; background:#6c757d" href="/?action=logout">Déconnexion</a>
    </div>

    <?php if ($flash): ?><div class="card"><?= esc((string) $flash) ?></div><?php endif; ?>

    <?php if ($action === 'detail'):
        $id = (int) ($_GET['id'] ?? 0);
        $sol = $service->find($id);
        if (!$sol): ?>
            <div class="card">Dossier introuvable.</div>
        <?php else:
            $users = $service->allUsers();
            $history = $service->history($id);
            $attachments = $service->attachments($id);
            ?>
            <div class="card">
                <a href="/">← Retour dashboard</a>
                <h2>Dossier #<?= (int) $sol['id'] ?> - <?= esc((string) $sol['title']) ?></h2>
                <p><?= nl2br(esc((string) $sol['description'])) ?></p>
                <p><span class="status">Statut: <?= esc((string) $sol['status']) ?></span></p>
                <p class="muted">Demandeur: <?= esc((string) ($sol['requester_email'] ?? '')) ?></p>
            </div>

            <div class="row">
                <div class="card">
                    <h3>Workflow flexible</h3>
                    <form method="post" action="/?action=update_workflow">
                        <input type="hidden" name="id" value="<?= (int) $sol['id'] ?>">
                        <label>Statut suivant</label>
                        <select name="next_status" required>
                            <?php foreach (WorkflowEngine::STATUSES as $status): ?>
                                <option value="<?= esc($status) ?>"><?= esc($status) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Réattribuer à</label>
                        <select name="assign_to">
                            <option value="">-- non assigné --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" <?= ((int) $sol['assigned_to'] === (int) $u['id']) ? 'selected' : '' ?>>
                                    <?= esc((string) $u['username']) ?> (<?= esc((string) $u['service']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Note</label>
                        <textarea name="note" rows="3" placeholder="Commentaire interne"></textarea>
                        <label><input type="checkbox" name="adhoc"> Autoriser transition ad-hoc (hors parcours initial)</label>
                        <button type="submit">Mettre à jour</button>
                    </form>
                </div>

                <div class="card">
                    <h3>GED / Pièces jointes</h3>
                    <form method="post" action="/?action=upload_attachment" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= (int) $sol['id'] ?>">
                        <input type="file" name="attachment" required>
                        <button type="submit">Ajouter une pièce jointe</button>
                    </form>
                    <ul>
                    <?php foreach ($attachments as $att): ?>
                        <li>
                            <a target="_blank" href="/?action=view_attachment&id=<?= (int) $att['id'] ?>"><?= esc((string) $att['original_name']) ?></a>
                            <span class="muted">(<?= esc((string) $att['mime_type']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <h3>Historique workflow</h3>
                <table>
                    <thead><tr><th>Date</th><th>Transition</th><th>Par</th><th>Assigné à</th><th>Note</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= esc((string) $h['created_at']) ?></td>
                            <td><?= esc((string) $h['from_status']) ?> → <?= esc((string) $h['to_status']) ?></td>
                            <td><?= esc((string) ($h['actor_username'] ?? 'n/a')) ?></td>
                            <td><?= esc((string) ($h['assignee_username'] ?? 'non assigné')) ?></td>
                            <td><?= esc((string) ($h['note'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else:
        $list = $service->listForUser((int) $user['id']);
        ?>
        <div class="card">
            <h1>Tableau de bord</h1>
            <p class="muted">Sollicitations à traiter pour l'utilisateur connecté.</p>
            <table>
                <thead><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Assigné</th><th>Maj</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>#<?= (int) $row['id'] ?></td>
                        <td><?= esc((string) $row['title']) ?></td>
                        <td><span class="status"><?= esc((string) $row['status']) ?></span></td>
                        <td><?= esc((string) ($row['assigned_username'] ?? 'non assigné')) ?></td>
                        <td><?= esc((string) $row['updated_at']) ?></td>
                        <td><a class="button" href="/?action=detail&id=<?= (int) $row['id'] ?>">Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
