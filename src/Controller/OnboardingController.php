<?php

namespace App\Controller;

use App\Entity\User;
use Stripe\StripeClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class OnboardingController extends AbstractController
{

    #[Route('/onboarding', name: 'app_onboarding')]
    #[IsGranted('ROLE_USER')]
    public function onboarding(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOtpEnabled()) {
            return $this->redirectToRoute('enableOtp');
        }
        
        return $this->render('onboarding/index.html.twig', [
            'steps' => $user->getOnboardingSteps()
        ]);
    }

    #[Route('/onboarding/stripe', name: 'stripe_onboarding')]
    public function stripeOnboarding(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $stripeAccount = $user->getStripeConnectId();

        return $this->render('onboarding/stripe.html.twig', [
            'stripeAccountId' => $stripeAccount
        ]);
    }

    #[Route('/onboarding/boxtal', name: 'boxtal_onboarding')]
    public function boxtalOnboarding(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setBoxtalApiKey($request->request->get('api_key'));
            $user->setBoxtalApiSecret($request->request->get('api_secret'));
            $user->setDeliveryPrice($request->request->get('delivery_price'));
            $entityManager->flush();

            return $this->redirectToRoute('app_onboarding');
        }

        return $this->render('onboarding/boxtal.html.twig');
    }
}
