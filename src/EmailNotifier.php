<?php

declare(strict_types=1);

namespace App;

final class EmailNotifier
{
    /** @var string */
    private $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * En environnement local, on log les mails dans un fichier.
     * Si SMTP est configuré côté php.ini, vous pouvez remplacer par mail().
     */
    public function send(string $to, string $subject, string $message): void
    {
        $content = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n\n",
            date('c'),
            $to,
            $subject,
            $message
        );

        file_put_contents($this->logFile, $content, FILE_APPEND);
    }
}
