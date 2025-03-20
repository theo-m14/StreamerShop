<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class IsOtpEnabledListener
{   
    public function __construct(private Security $security, private TokenStorageInterface $tokenStorage)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {   
        // don't do anything if it's not the main request
        if (!$event->isMainRequest()) {
            return;
        }

        //Get user if exist
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        //Get request
        $request = $event->getRequest();

        if ($user) {
            // Étape 1 : Vérification 2FA

            if (!$user->isOtpEnabled()) {
                $allowedRoutes = ['enableOtp', 'checkOtp', 'generateQrCode', 'verify/email'];
                if (!in_array($request->get('_route'), $allowedRoutes)) {
                    $event->setResponse(new RedirectResponse('/enableOtp'));
                }
            }
            // Étape 2 : Vérification onboarding si 2FA OK
            
            else if (!$user->isOnboardingComplete()) {
                $allowedRoutes = ['app_onboarding', 'stripe_onboarding', 'boxtal_onboarding','2fa_login','createStripeSession','createStripeAccount','setUserStripeRegistered'];
                if (!in_array($request->get('_route'), $allowedRoutes)) {
                    $event->setResponse(new RedirectResponse('/onboarding'));
                }
            }
        }
    }
}
