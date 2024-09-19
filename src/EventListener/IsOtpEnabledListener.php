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

        error_log("je redirige pas");

        //Get user if exist
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        //Get request
        $request = $event->getRequest();

        //If user is not enabled and not on the enableOtp page, redirect to enableOtp page
        if($user && !$user->isOtpEnabled()){
            
            error_log("Je redirige");
            if($request->getPathInfo() != '/generateQrCode' && $request->getPathInfo() != '/enableOtp' && $request->getPathInfo() != '/checkOtp' && $request->getPathInfo() != '/verify/email') {
                //Rediriger vers la page d'activation de son authenticator
                $redirectResponse = new RedirectResponse('/enableOtp');
                $event->setResponse($redirectResponse);
            }
        }
    }
}
