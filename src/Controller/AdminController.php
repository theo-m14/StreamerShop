<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Subscription;
use App\Form\SubscriptionType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    //create subscription for a user with form
    #[Route('/users/{id}/create-subscription', name: 'app_admin_create_subscription')]
    public function createSubscription(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
      $subscription = new Subscription();
      $form = $this->createForm(SubscriptionType::class, $subscription);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        $stripeId = uniqid() . '-manual';

        $currentPeriodStart = $subscription->getCurrentPeriodStart();
        $currentPeriodEnd = $subscription->getCurrentPeriodEnd();
        $subscription->setUser($user);
        $subscription->setStripeId($stripeId);
        $subscription->setActive(true);
        $entityManager->persist($subscription);
        $entityManager->flush();

        $this->addFlash('success', 'Abonnement créé avec succès');

        return $this->redirectToRoute('app_admin_users');
      }

      return $this->render('admin/subscription_form.html.twig', [
        'form' => $form->createView(),
      ]);
    }

    //edit subscription for a user with form
    #[Route('/users/{id}/edit-subscription', name: 'app_admin_edit_subscription')]
    public function editSubscription(Subscription $subscription, EntityManagerInterface $entityManager, Request $request): Response
    {
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Abonnement modifié avec succès');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/subscription_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
