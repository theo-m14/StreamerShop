<?php

namespace App\Tests;

use App\Entity\Plan;
use App\Entity\User;
use App\Entity\Invoice;
use App\Entity\Subscription;
use App\Tests\Traits\TestHelpers;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

class AccountControllerTest extends WebTestCase
{
    use TestHelpers;

    private $client;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Nettoyer la base de données avant chaque test
    }

    //Test page double authentification setup with qrCode
    public function testEnableOtp(): void
    {
        $user = $this->createUser('testEnableOtp@example.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/enableOtp');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img#qr-code');

        $user = $this->userRepository->findOneBy(['email' => 'testEnableOtp@example.com']);
        $this->assertNotNull($user->getGoogleAuthenticatorSecret());
    }

    //Test generate qrCode
    public function testGenerateQrCode(): void
    {
        $user = $this->createUser('testGenerateQrCode@example.com');
        $this->client->loginUser($user);

        $mockGoogleAuthenticator = $this->createMock(GoogleAuthenticatorInterface::class);
        $mockGoogleAuthenticator->method('generateSecret')->willReturn("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->client->getContainer()->set(GoogleAuthenticatorInterface::class, $mockGoogleAuthenticator);

        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();

        $this->client->request('GET', '/generateQrCode');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
    }

    //Test page validation code OTP
    public function testCheckOtp(): void
    {
        $user = $this->createUser('testCheckOtp@example.com');
        $this->client->loginUser($user);

        // Simuler l'activation de l'OTP
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();

        // Simuler un code OTP valide en utilisant un mock du GoogleAuthenticatorInterface
        $mockGoogleAuthenticator = $this->createMock(GoogleAuthenticatorInterface::class);
        $mockGoogleAuthenticator->method('checkCode')->willReturn(true);
        $this->client->getContainer()->set(GoogleAuthenticatorInterface::class, $mockGoogleAuthenticator);
        
        $this->client->request('POST', '/checkOtp', ['code' => '123456']);
        
        $this->assertResponseRedirects('/');
        
        $user = $this->userRepository->findOneBy(['email' => 'testCheckOtp@example.com']);
        $this->assertTrue($user->isOtpEnabled());
    }

    //Test page account
    public function testAccount(): void
    {   
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $user = $this->createUser('testAccount@example.com');
        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();

        $plan = $this->createPlan();

        $subscription = $this->createSubscription($user, $plan);


        $invoice1 = $this->createInvoice($user, $subscription);
        
        $invoice2 = $this->createInvoice($user, $subscription);

        $invoiceRepository = $this->entityManager->getRepository(Invoice::class);
        $this->assertCount(2, $invoiceRepository->findBy(['user' => $user]));

        $this->client->loginUser($user,"main",[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);

        $this->client->request('GET', '/mon-compte');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('table tbody tr:nth-of-type(1) .invoice-amount', $invoice2->getAmountPaid()/100 . ' €');
        $this->assertSelectorTextContains('table tbody tr:nth-of-type(2) .invoice-amount', $invoice1->getAmountPaid()/100 . ' €');

        $this->assertSelectorTextContains('table tbody tr:nth-of-type(1) .invoice-date', $invoice2->getCreatedAt()->format('d/m/Y'));
        $this->assertSelectorTextContains('table tbody tr:nth-of-type(2) .invoice-date', $invoice1->getCreatedAt()->format('d/m/Y'));


        $this->assertEquals($invoice2->getHostedInvoiceUrl(), $this->client->getCrawler()->filter('table tbody tr:nth-of-type(1) .invoice-link a')->attr('href'));
        $this->assertEquals($invoice1->getHostedInvoiceUrl(), $this->client->getCrawler()->filter('table tbody tr:nth-of-type(2) .invoice-link a')->attr('href'));


        $this->assertSelectorTextContains('a.stripe-button', 'Gérer mes abonnements');

        $this->assertSelectorTextContains('p', 'Vous avez un abonnement actif depuis le ' . $subscription->getCurrentPeriodStart()->format('d/m/Y') . ' avec le plan ' . $subscription->getPlan()->getName());

    }

    public function createInvoice(User $user, Subscription $subscription): Invoice
    {
        $invoice = new Invoice();
        $invoice->setSubscription($subscription);
        $invoice->setAmountPaid(1000);
        $invoice->setStripeId('in_123456');
        $invoice->setNumber('123456');
        $invoice->setCreatedAt(new \DateTimeImmutable());
        $invoice->setHostedInvoiceUrl('https://example.com/invoice/123456');
        $invoice->setUser($user);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }


    public function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Invoice')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Subscription')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Plan')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->flush();
        $this->entityManager->close();
    }

}
