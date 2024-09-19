<?php

namespace App\Controller;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use App\Repository\InvoiceRepository;
class AccountController extends AbstractController
{   
    #[IsGranted('ROLE_USER')]
    #[Route('/mon-compte', name: 'app_account')]
    public function index(InvoiceRepository $invoiceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $invoices = $invoiceRepository->findBy(['user' => $user]);

        usort($invoices, function($a, $b) {
            return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp();
        });

        $stripeUserPortal = $this->getParameter('stripe_user_portal');

        return $this->render('account/index.html.twig', [
            'invoices' => $invoices,
            'stripeUserPortal' => $stripeUserPortal,
        ]);
    }

    #[Route('/enableOtp', name: 'enableOtp', methods:['GET'])]
    public function enableOtp(GoogleAuthenticatorInterface $googleAuthenticatorInterface,EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if($user->isOtpEnabled()){
            $this->addFlash('error','Vous avez déjà activé votre Authenticator');
            return $this->redirectToRoute('app_home');
        }
        if(!$user->isGoogleAuthenticatorEnabled()){
            $secret = $googleAuthenticatorInterface->generateSecret();
        } else{
            $secret = $user->getGoogleAuthenticatorSecret();
        }
        $user->setGoogleAuthenticatorSecret($secret);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('account/enableOtp.html.twig',['code' => $secret]);
    }

    #[Route('/generateQrCode', name: 'generateQrCode', methods:['GET'])]
    public function generateQrCode(GoogleAuthenticatorInterface $googleAuthenticatorInterface,TokenStorageInterface $tokenStorage): Response
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

    #[Route('/checkOtp', name: 'checkOtp', methods:['POST'])]
    public function checkOtp(Request $request,GoogleAuthenticatorInterface $googleAuthenticatorInterface,EntityManagerInterface $entityManager): Response
    {

        /** @var TwoFactorInterface $user */
        $user = $this->getUser();
        $code = $request->request->get('code');
        $otpEnable = $googleAuthenticatorInterface->checkCode($user,$code);

        if(!$otpEnable){
            $this->addFlash('error','Le code est incorrect, merci de supprimer votre clé enregistrée sur l\'application et rescanner le QR Code');
            return $this->redirectToRoute('enableOtp');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setOtpEnabled(true);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success','Votre Authenticator a été correctement configuré');
        return $this->redirectToRoute('app_home');
    }
}
