<?php

namespace Tests\AppBundle\Controller\EnMarche;

use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\MysqlWebTestCase;

abstract class AbstractEventControllerTest extends MysqlWebTestCase
{
    use ControllerTestTrait;

    public function testAnonymousUserCanRegisterToEventWhileLoginIn()
    {
        $eventUrl = $this->getEventUrl();
        $crawler = $this->client->request('GET', $eventUrl);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('1 inscrit', trim($crawler->filter('.committee-event-attendees')->text()));

        $registrationLink = $crawler->selectLink('Je veux participer');

        $eventRegistrationUrl = $eventUrl.'/inscription';

        $this->assertSame($eventRegistrationUrl, $registrationLink->attr('href'));

        $crawler = $this->client->click($registrationLink->link());

        $this->assertCount(1, $connectLink = $crawler->selectLink('Connectez-vous'));

        $crawler = $this->client->click($connectLink->link());

        $this->client->submit($crawler->selectButton('Je me connecte')->form([
            '_adherent_email' => 'benjyd@aol.com',
            '_adherent_password' => 'HipHipHip',
        ]));

        $this->assertClientIsRedirectedTo($eventRegistrationUrl, $this->client);
    }

    abstract protected function getEventUrl(): string;
}
