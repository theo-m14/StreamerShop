<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $this->userRepository = $em->getRepository(User::class);

        // Remove any existing users from the test database
        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();

        // Create a User fixture
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new User())->setEmail('email@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setGoogleAuthenticatorSecret("76JR664X2PP5UGMEN57JYBCPHXBTOJ2HEOPY2K7N3QMJV4VV7PPA");
        $user->setOtpEnabled(true);
        $em->persist($user);
        $em->flush();
    }

    public function testLogin(): void
    {
        // Denied - Can't login with invalid email address.
        $this->client->request('GET', '/connexion');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Se connecter', [
            '_username' => 'doesNotExist@example.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();

        // Ensure we do not reveal if the user exists or not.
        self::assertSelectorTextContains('.alert-danger', 'Identifiants invalides.');

        // Denied - Can't login with invalid password.
        $this->client->request('GET', '/connexion');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Se connecter', [
            '_username' => 'email@example.com',
            '_password' => 'bad-password',
        ]);

        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();

        // Ensure we do not reveal the user exists but the password is wrong.
        self::assertSelectorTextContains('.alert-danger', 'Identifiants invalides.');

        // Success - Login with valid credentials is allowed.
        $this->client->submitForm('Se connecter', [
            '_username' => 'email@example.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();

        self::assertResponseRedirects('/2fa');

        // Suivre la redirection vers la page 2FA
        $this->client->followRedirect();


        $this->client->submitForm('Connexion', [
            '_auth_code' => '123456',
        ]);
        
        $user = $this->userRepository->findOneBy(['email' => 'email@example.com']);
        $this->client->loginUser($user,'main', ["2fa_complete" => true]);

        $this->client->request('GET', '/mon-compte');

        self::assertSelectorNotExists('.alert-danger');
        self::assertResponseIsSuccessful();
    }
}
