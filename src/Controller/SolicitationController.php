<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Solicitation;
use App\Entity\WorkflowHistory;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class SolicitationController extends AbstractController
{
    #[Route('/solicitation/{id}', name: 'app_solicitation_show')]
    public function show(Solicitation $solicitation, EntityManagerInterface $em): Response
    {
        return $this->render('solicitation/show.html.twig', [
            'solicitation' => $solicitation,
            'users' => $em->getRepository(User::class)->findAll(),
            'history' => $em->getRepository(WorkflowHistory::class)->findBy(['solicitation' => $solicitation], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/solicitation/{id}/transition', name: 'app_solicitation_transition', methods: ['POST'])]
    public function transition(Solicitation $solicitation, Request $request, WorkflowInterface $solicitationStateMachine, EntityManagerInterface $em, NotificationService $notif): RedirectResponse
    {
        $transition = (string) $request->request->get('transition');
        $assignToId = $request->request->getInt('assign_to');
        $note = (string) $request->request->get('note');

        $from = $solicitation->getStatus();
        if ($transition && $solicitationStateMachine->can($solicitation, $transition)) {
            $solicitationStateMachine->apply($solicitation, $transition);
            if ($assignToId > 0) {
                $assignee = $em->getRepository(User::class)->find($assignToId);
                $solicitation->setAssignedTo($assignee);
                if ($assignee) {
                    $notif->notify($assignee->getEmail(), 'Nouveau dossier à traiter', 'Le dossier #' . $solicitation->getId() . ' vous est attribué.');
                }
            }

            $history = (new WorkflowHistory())
                ->setSolicitation($solicitation)
                ->setActor($this->getUser())
                ->setFromStatus($from)
                ->setToStatus($solicitation->getStatus())
                ->setNote($note);

            $solicitation->touch();
            $em->persist($history);
            $em->flush();

            if ($solicitation->getStatus() === 'terminee' && $solicitation->getRequesterEmail()) {
                $notif->notify($solicitation->getRequesterEmail(), 'Dossier traité', 'Votre dossier est terminé.');
            }
        }

        return $this->redirectToRoute('app_solicitation_show', ['id' => $solicitation->getId()]);
    }

    #[Route('/solicitation/{id}/attachment', name: 'app_solicitation_attachment', methods: ['POST'])]
    public function upload(Solicitation $solicitation, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('attachment');
        if ($file) {
            $name = uniqid('att_', true) . '.' . $file->guessExtension();
            $file->move($this->getParameter('kernel.project_dir') . '/storage/attachments', $name);

            $att = (new Attachment())
                ->setSolicitation($solicitation)
                ->setOriginalName($file->getClientOriginalName())
                ->setStoredPath($name)
                ->setMimeType($file->getMimeType());
            $em->persist($att);
            $em->flush();
        }

        return $this->redirectToRoute('app_solicitation_show', ['id' => $solicitation->getId()]);
    }

    #[Route('/attachments/{path}', name: 'app_attachment_view')]
    public function view(string $path): Response
    {
        $fullPath = $this->getParameter('kernel.project_dir') . '/storage/attachments/' . basename($path);
        if (!is_file($fullPath)) {
            throw $this->createNotFoundException();
        }

        return $this->file($fullPath, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
