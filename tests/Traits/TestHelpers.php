<?php

namespace App\Tests\Traits;

use App\Entity\Plan;
use App\Entity\User;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait TestHelpers
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;


    public function initializeDependencies(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): void
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    //Create user for test
    private function createUser(string $email = 'test@example.com', string $password = 'password', array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    //Create plan for test
    private function createPlan(string $name = 'Test Plan', int $price = 1000): Plan
    {
        $plan = new Plan();
        $plan->setName($name);
        $plan->setStripeId('plan_test123');
        $plan->setPrice($price);
        $plan->setSlug('test-plan');
        $plan->setCreatedAt(new \DateTimeImmutable());
        $plan->setPaymentLink('https://example.com/payment');
        
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
        
        return $plan;
    }

    public function createSubscription(User $user, Plan $plan): Subscription
    {
        $subscription = new Subscription();
        $subscription->setStripeId('sub_123456');
        $subscription->setPlan($plan);
        $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
        $subscription->setCurrentPeriodEnd((new \DateTime())->modify('+1 month'));
        $subscription->setActive(true);
        $subscription->setUser($user);


        $this->entityManager->persist($subscription);
        $this->entityManager->refresh($user);
        $this->entityManager->flush();

        return $subscription;
    }
} 