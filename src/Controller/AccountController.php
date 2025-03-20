<?php

namespace App\Controller;

use Stripe\StripeClient;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use App\Repository\InvoiceRepository;
use Endroid\QrCode\Encoding\Encoding;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

class AccountController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/mon-compte', name: 'app_account')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/index.html.twig', [
            'connectedAccountId' => $user->getStripeConnectId()
        ]);
    }

    #[Route('/enableOtp', name: 'enableOtp', methods: ['GET'])]
    public function enableOtp(GoogleAuthenticatorInterface $googleAuthenticatorInterface, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->isOtpEnabled()) {
            $this->addFlash('error', 'Vous avez déjà activé votre Authenticator');
            return $this->redirectToRoute('app_home');
        }
        if (!$user->isGoogleAuthenticatorEnabled()) {
            $secret = $googleAuthenticatorInterface->generateSecret();
        } else {
            $secret = $user->getGoogleAuthenticatorSecret();
        }
        $user->setGoogleAuthenticatorSecret($secret);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('account/enableOtp.html.twig', ['code' => $secret]);
    }

    #[Route('/generateQrCode', name: 'generateQrCode', methods: ['GET'])]
    public function generateQrCode(GoogleAuthenticatorInterface $googleAuthenticatorInterface, TokenStorageInterface $tokenStorage): Response
    {

        $user = $tokenStorage->getToken()->getUser();
        if (!($user instanceof TwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        $qrCode = $googleAuthenticatorInterface->getQRContent($user);

        $qrCodeImage = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCode)
            ->encoding(new Encoding('UTF-8'))
            ->size(200)
            ->margin(0)
            ->build();

        return new Response($qrCodeImage->getString(), 200, ['Content-Type' => 'image/png']);
    }

    #[Route('/checkOtp', name: 'checkOtp', methods: ['POST'])]
    public function checkOtp(Request $request, GoogleAuthenticatorInterface $googleAuthenticatorInterface, EntityManagerInterface $entityManager): Response
    {

        /** @var TwoFactorInterface $user */
        $user = $this->getUser();
        $code = $request->request->get('code');
        $otpEnable = $googleAuthenticatorInterface->checkCode($user, $code);

        if (!$otpEnable) {
            $this->addFlash('error', 'Le code est incorrect, merci de supprimer votre clé enregistrée sur l\'application et rescanner le QR Code');
            return $this->redirectToRoute('enableOtp');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setOtpEnabled(true);
        $entityManager->persist($user);
        $entityManager->flush();

        if (!$user->isOnboardingComplete()) {
            return $this->redirectToRoute('app_onboarding');
        }
        return $this->redirectToRoute('app_home');
    }

    #[Route('/connectStripeAccount', name: 'connectStripeAccount', methods: ['GET'])]
    public function connectStripeAccount(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $stripe = new StripeClient($this->getParameter('stripe_api_key'));
        $account = $stripe->accounts->create([
            'type' => 'standard',
            'email' => $user->getEmail(),
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'country' => 'FR',
            'business_type' => 'individual',
        ]);

        // $user->setStripeConnectId($account->id);
        // $entityManager->persist($user);
        // $entityManager->flush();

        // Stocker l'account ID pour une utilisation ultérieure
        $accountLinks = $stripe->accountLinks->create([
            'account' => $account->id,
            'refresh_url' => $this->generateUrl('connectStripeAccount', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'return_url' => $this->generateUrl('app_account', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'type' => 'account_onboarding',
        ]);

        return $this->redirect($accountLinks->url);
    }

    #[Route('/createStripeAccount', name: 'createStripeAccount', methods: ['GET'])]
    public function createStripeAccount(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getStripeConnectId()) {
            return new JsonResponse(array(
                'account' => $user->getStripeConnectId()
            ));
        }

        try {
            $stripe = new StripeClient($this->getParameter('stripe_api_key'));
            $account = $stripe->accounts->create();

            $user->setStripeConnectId($account->id);
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(array(
                'account' => $account->id,
            ));
        } catch (\Exception $e) {
            return new JsonResponse(array(
                'error' => $e->getMessage()
            ), 500);
        }
    }

    #[Route('/createStripeSession', name: 'createStripeSession', methods: ['POST'])]
    public function createStripeSession(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $account = $data['account'];

        try {
            $stripe = new StripeClient($this->getParameter('stripe_api_key'));
            $account_session = $stripe->accountSessions->create([
                'account' => $account,
                'components' => [
                    'notification_banner' => [
                        'enabled' => true,
                        'features' => ['external_account_collection' => true],
                    ],
                    'account_onboarding' => [
                        'enabled' => true,
                    ],
                    'account_management' => [
                        'enabled' => true,
                        'features' => ['external_account_collection' => true],
                    ],
                    'payments' => [
                        'enabled' => true,
                        'features' => [
                            'refund_management' => true,
                            'dispute_management' => true,
                            'capture_payments' => true,
                            'destination_on_behalf_of_charge_management' => false,
                        ],
                    ],
                    'balances' => [
                        'enabled' => true,
                        'features' => [
                            'instant_payouts' => true,
                            'standard_payouts' => true,
                            'edit_payout_schedule' => true,
                        ],
                    ],
                    'documents' => [
                        'enabled' => true,
                    ],
                ],
            ]);

            return new JsonResponse(array(
                'client_secret' => $account_session->client_secret
            ));
        } catch (\Exception $e) {
            return new JsonResponse(array(
                'error' => $e->getMessage()
            ), 500);
        }
    }

    #[Route('/setUserStripeRegistered', name: 'setUserStripeRegistered', methods: ['GET'])]
    public function setUserStripeRegistered(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setIsStripeRegistered(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(array(
            'success' => true
        ));
    }
}
