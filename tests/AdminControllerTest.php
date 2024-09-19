<?php

namespace App\Tests;

use DateTime;
use App\Tests\Traits\TestHelpers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;

class AdminControllerTest extends WebTestCase
{
    use TestHelpers;

    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.user_password_hasher');
        $this->initializeDependencies($entityManager, $passwordHasher);
    }

    public function testUnloggedUserCannotAccessAdmin(): void
    {   
        $this->client->request('GET', '/admin/users');

        $this->assertResponseRedirects('/connexion');
    }

    public function testLoggedUserCannotAccessAdmin(): void
    {
        $user = $this->createUser('userNotAdmin@example.com', 'password', ['ROLE_USER']);
        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();
        $this->client->loginUser($user, 'main',[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessAdmin(): void
    {
        $user = $this->createUser('admin@example.com', 'password', ['ROLE_ADMIN']);
        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();
        $this->client->loginUser($user, 'main',[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);
        
        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminUsersList(): void
    {
        $user = $this->createUser('admin@example.com', 'password', ['ROLE_ADMIN']);
        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $this->entityManager->flush();
        $this->client->loginUser($user, 'main',[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);

        $oneMonthLater = $this->createUserWithSubscription();

        $this->client->request('GET', '/admin/users');

        // Format de la date attendu
        $dateFormat = 'd/m/Y H:i:s';
        $expectedDate = $oneMonthLater->format($dateFormat);

        // Vérification des dates dans le tableau
        $this->assertCount(10, $this->client->getCrawler()->filter('td:contains("' . $expectedDate . '")'));
        //Vérification de la présence de 11 emails
        $this->assertCount(11, $this->client->getCrawler()->filter('td:contains("@example.com")'));

        $this->assertCount(5, $this->client->getCrawler()->filter('td:contains("ROLE_ESSENTIAL")'));
        $this->assertCount(5, $this->client->getCrawler()->filter('td:contains("ROLE_PREMIUM")'));
    }

    public function createUserWithSubscription(): DateTime
    {   
        
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $oneMonthLater = (clone $now)->modify('+1 month');

        $plan = $this->createPlan('Essential', 1000);
        $plan2 = $this->createPlan('Premium', 2000);

        for ($i = 0; $i < 10; $i++) {
            $user = $this->createUser('user' . $i . '@example.com', 'password', ['ROLE_USER']);
            if($i % 2 == 0){
                $subscription = $this->createSubscription($user, $plan);
            }else{
                $subscription = $this->createSubscription($user, $plan2);
            }
            $subscription->setCurrentPeriodEnd(clone $oneMonthLater);
            $subscription->setActive(true);
            $this->entityManager->persist($subscription);
            $this->entityManager->refresh($user);
            $userTab[] = $user;
        }

        $this->entityManager->flush();
        return $oneMonthLater;
    }

    public function testAdminCreateSubscription(): void
    {
        $user = $this->createUser('admin@example.com', 'password', ['ROLE_ADMIN']);
        $userToEdit = $this->createUser('userToEdit@example.com', 'password', ['ROLE_USER']);
        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $plan = $this->createPlan('Premium', 2000);
        $this->entityManager->flush();
        $this->client->loginUser($user, 'main',[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        $link = $this->client->getCrawler()->filter('table a')->eq(1)->link();

        $this->client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $this->client->getCrawler()->selectButton('Valider')->form();

        // Inclure la date et l'heure dans le format
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $form['subscription[currentPeriodStart]']->setValue($now->format('Y-m-d H:i:s'));
        $nowPlusOneMonth = (clone $now)->modify('+1 month');
        $form['subscription[currentPeriodEnd]']->setValue($nowPlusOneMonth->format('Y-m-d H:i:s'));

        $form['subscription[plan]']->setValue($plan->getId());
        $form['subscription[user]']->setValue($user->getId());
        $form["subscription[_token]"]->setValue($form["subscription[_token]"]->getValue());
        $this->client->submit($form);


        $this->assertResponseRedirects('/admin/users');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('table', $nowPlusOneMonth->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y H:i:s'));
    }

    public function testAdminEditSubscription(): void
    {
        $user = $this->createUser('admin@example.com', 'password', ['ROLE_ADMIN']);
        $userToEdit = $this->createUser('userToEdit@example.com', 'password', ['ROLE_USER']);

        $user->setOtpEnabled(true);
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $plan = $this->createPlan('Premium', 2000);
        $subscription = $this->createSubscription($userToEdit, $plan);
        $this->entityManager->flush();
        $this->client->loginUser($user, 'main',[TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true]);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        $link = $this->client->getCrawler()->filter('table a')->eq(1)->link();

        $this->client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $this->client->getCrawler()->selectButton('Valider')->form();

        $now = new \DateTime('now');
        $nowPlusOneMonth = (clone $now)->modify('+1 month');
        $form['subscription[currentPeriodStart]']->setValue($now->format('Y-m-d H:i:s'));
        $form['subscription[currentPeriodEnd]']->setValue($nowPlusOneMonth->format('Y-m-d H:i:s'));

        $form["subscription[_token]"]->setValue($form["subscription[_token]"]->getValue());
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('table', $nowPlusOneMonth->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y H:i:s'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Subscription')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Plan')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->flush();
        $this->entityManager->close();
    }

    
}