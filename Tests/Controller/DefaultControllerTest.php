<?php

namespace Treetop1500\SecurityReportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/services/security-check/e6d303ce6a0fe8e656859e3f981c5d1');

        $this->assertContains('Security Check Report', $client->getResponse()->getContent());
    }
}
