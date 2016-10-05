<?php

namespace Treetop1500\SecurityReportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        /* @todo wrap up php unit testing

        $config = $this->getParameter('treetop1500_security_report.config');
        $client = static::createClient();
        $crawler = $client->request('GET', '/services/security-checker/'.$config['key']);

        $this->assertContains('Security Check Report',
          $client->getResponse()->getContent(),
          "The security report test failed. The URL did not contain the appropriate response text."
        );

        */
    }
}
