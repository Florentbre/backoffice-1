<?php

namespace App\Controller;

use App\Entity\Solicitation;
use App\Service\IncomingJsonImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em, IncomingJsonImporter $importer): Response
    {
        $importer->import();
        $list = $em->getRepository(Solicitation::class)->findBy(['assignedTo' => $this->getUser()], ['updatedAt' => 'DESC']);

        return $this->render('dashboard/index.html.twig', ['solicitations' => $list]);
    }
}
