<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PlanRepository $planRepository): Response
    {
        $plans = $planRepository->findAll();

        return $this->render('home/index.html.twig', [
            'plans' => $plans,
        ]);
    }

    //Page de mentions légales
    #[Route('/mentions-legales', name: 'app_legal_mentions')]
    public function legalMentions(): Response
    {
        return $this->render('home/legal_mentions.html.twig');
    }

    //Page de conditions d'utilisation
    #[Route('/conditions-d-utilisation', name: 'app_usage_conditions')]
    public function usageConditions(): Response
    {
        return $this->render('home/usage_conditions.html.twig');
    }

    //Page de conditions de vente
    #[Route('/conditions-de-vente', name: 'app_sales_conditions')]
    public function salesConditions(): Response
    {
        return $this->render('home/sales_conditions.html.twig');
    }
}
