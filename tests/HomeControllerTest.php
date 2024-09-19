<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testLegalMentions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'MENTIONS LÉGALES');
    }

    public function testUsageConditions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conditions-d-utilisation');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'CONDITIONS GÉNÉRALES D\'UTILISATION');
    }
    
    public function testSalesConditions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conditions-de-vente');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'CONDITIONS GÉNÉRALES DE VENTES');
    }
    
}