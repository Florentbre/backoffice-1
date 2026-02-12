<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class SolicitationService
{
    /** @var Database */
    private $database;

    /** @var WorkflowEngine */
    private $workflow;

    /** @var EmailNotifier */
    private $emailNotifier;

    /** @var string */
    private $incomingDir;

    /** @var string */
    private $attachmentsDir;

    public function __construct(
        Database $database,
        WorkflowEngine $workflow,
        EmailNotifier $emailNotifier,
        string $incomingDir,
        string $attachmentsDir
    ) {
        $this->database = $database;
        $this->workflow = $workflow;
        $this->emailNotifier = $emailNotifier;
        $this->incomingDir = $incomingDir;
        $this->attachmentsDir = $attachmentsDir;
    }

    public function importIncomingJson(): void
    {
        foreach (glob($this->incomingDir . '/*.json') ?: [] as $filePath) {
            $content = file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            $payload = json_decode($content, true);
            if (!is_array($payload) || empty($payload['external_id']) || empty($payload['title'])) {
                rename($filePath, $filePath . '.invalid');
                continue;
            }

            $stmt = $this->database->pdo()->prepare('INSERT OR IGNORE INTO solicitations (external_id, title, description, requester_email, status, assigned_to, payload_json, created_at, updated_at)
                VALUES (:external_id, :title, :description, :requester_email, :status, :assigned_to, :payload_json, :created_at, :updated_at)');

            $now = date('c');
            $stmt->execute([
                ':external_id' => (string) $payload['external_id'],
                ':title' => (string) $payload['title'],
                ':description' => (string) ($payload['description'] ?? ''),
                ':requester_email' => (string) ($payload['requester_email'] ?? ''),
                ':status' => 'recu',
                ':assigned_to' => $this->defaultAssigneeId(),
                ':payload_json' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            rename($filePath, $filePath . '.processed');
        }
    }

    public function listForUser(int $userId): array
    {
        $sql = 'SELECT s.*, u.username AS assigned_username
                FROM solicitations s
                LEFT JOIN users u ON u.id = s.assigned_to
                WHERE s.assigned_to = :user_id OR s.assigned_to IS NULL
                ORDER BY s.updated_at DESC';
        $stmt = $this->database->pdo()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array|null */
    public function find(int $id)
    {
        $stmt = $this->database->pdo()->prepare('SELECT * FROM solicitations WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $sol = $stmt->fetch();

        return $sol ?: null;
    }

    public function allUsers(): array
    {
        return $this->database->pdo()->query('SELECT id, username, service, email FROM users ORDER BY username')->fetchAll();
    }

    public function history(int $solicitationId): array
    {
        $stmt = $this->database->pdo()->prepare('SELECT h.*, u.username AS actor_username, a.username AS assignee_username
            FROM workflow_history h
            LEFT JOIN users u ON u.id = h.actor_id
            LEFT JOIN users a ON a.id = h.assigned_to
            WHERE solicitation_id = :id
            ORDER BY h.created_at DESC');
        $stmt->execute([':id' => $solicitationId]);

        return $stmt->fetchAll();
    }

    /** @param int|null $assignTo */
    public function updateWorkflow(int $solicitationId, int $actorId, string $nextStatus, $assignTo, string $note, bool $forceAdHoc): void
    {
        $sol = $this->find($solicitationId);
        if (!$sol) {
            throw new RuntimeException('Dossier introuvable');
        }

        $currentStatus = (string) $sol['status'];
        $this->workflow->assertTransitionAllowed($currentStatus, $nextStatus, $forceAdHoc);

        $stmt = $this->database->pdo()->prepare('UPDATE solicitations SET status = :status, assigned_to = :assigned_to, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':status' => $nextStatus,
            ':assigned_to' => $assignTo,
            ':updated_at' => date('c'),
            ':id' => $solicitationId,
        ]);

        $insertHistory = $this->database->pdo()->prepare('INSERT INTO workflow_history (solicitation_id, from_status, to_status, note, actor_id, assigned_to, created_at)
            VALUES (:solicitation_id, :from_status, :to_status, :note, :actor_id, :assigned_to, :created_at)');
        $insertHistory->execute([
            ':solicitation_id' => $solicitationId,
            ':from_status' => $currentStatus,
            ':to_status' => $nextStatus,
            ':note' => $note,
            ':actor_id' => $actorId,
            ':assigned_to' => $assignTo,
            ':created_at' => date('c'),
        ]);

        $this->notifyActors($solicitationId, $nextStatus, $assignTo, (string) ($sol['requester_email'] ?? ''));
    }

    public function addAttachment(int $solicitationId, array $file, int $userId): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload invalide.');
        }

        $extension = pathinfo((string) $file['name'], PATHINFO_EXTENSION);
        $storedName = uniqid('att_', true) . ($extension ? '.' . $extension : '');
        $destination = $this->attachmentsDir . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible de stocker la pièce jointe.');
        }

        $stmt = $this->database->pdo()->prepare('INSERT INTO attachments (solicitation_id, original_name, stored_name, mime_type, uploaded_by, created_at)
            VALUES (:solicitation_id, :original_name, :stored_name, :mime_type, :uploaded_by, :created_at)');
        $stmt->execute([
            ':solicitation_id' => $solicitationId,
            ':original_name' => (string) $file['name'],
            ':stored_name' => $storedName,
            ':mime_type' => (string) ($file['type'] ?? 'application/octet-stream'),
            ':uploaded_by' => $userId,
            ':created_at' => date('c'),
        ]);
    }

    public function attachments(int $solicitationId): array
    {
        $stmt = $this->database->pdo()->prepare('SELECT * FROM attachments WHERE solicitation_id = :id ORDER BY created_at DESC');
        $stmt->execute([':id' => $solicitationId]);

        return $stmt->fetchAll();
    }

    /** @param int|null $assignedTo */
    private function notifyActors(int $solicitationId, string $status, $assignedTo, string $requesterEmail): void
    {
        if ($assignedTo) {
            $stmt = $this->database->pdo()->prepare('SELECT email, username FROM users WHERE id = :id');
            $stmt->execute([':id' => $assignedTo]);
            $user = $stmt->fetch();

            if ($user) {
                $this->emailNotifier->send(
                    (string) $user['email'],
                    "[Backoffice] Dossier #$solicitationId à traiter",
                    "Le dossier #$solicitationId est maintenant au statut '$status' et vous est attribué."
                );
            }
        }

        if ($requesterEmail !== '' && $status === 'terminee') {
            $this->emailNotifier->send(
                $requesterEmail,
                "Votre dossier #$solicitationId est terminé",
                "Bonjour,\nVotre demande #$solicitationId a été traitée et clôturée."
            );
        }
    }

    /** @return int|null */
    private function defaultAssigneeId()
    {
        $stmt = $this->database->pdo()->query("SELECT id FROM users WHERE role = 'agent' ORDER BY id LIMIT 1");
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }
}
