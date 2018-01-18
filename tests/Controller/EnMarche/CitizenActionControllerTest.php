<?php

namespace Tests\AppBundle\Controller\EnMarche;

use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\DataFixtures\ORM\LoadCitizenActionCategoryData;
use AppBundle\DataFixtures\ORM\LoadCitizenActionData;
use AppBundle\DataFixtures\ORM\LoadCitizenInitiativeCategoryData;
use AppBundle\DataFixtures\ORM\LoadCitizenInitiativeData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectCategoryData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectCategorySkillData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectSkillData;
use AppBundle\DataFixtures\ORM\LoadEventCategoryData;
use AppBundle\DataFixtures\ORM\LoadEventData;
use AppBundle\Entity\CitizenInitiative;
use AppBundle\Entity\EventInvite;
use AppBundle\Entity\EventRegistration;
use AppBundle\Mailer\Message\CitizenInitiativeCreationConfirmationMessage;
use AppBundle\Mailer\Message\CitizenInitiativeInvitationMessage;
use AppBundle\Mailer\Message\CitizenInitiativeRegistrationConfirmationMessage;
use AppBundle\Mailer\Message\CommitteeCitizenInitiativeNotificationMessage;
use AppBundle\Mailer\Message\CommitteeCitizenInitiativeOrganizerNotificationMessage;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 */
class CitizenActionControllerTest extends AbstractEventControllerTest
{
    private $repository;

    public function testRegisteredAdherentUserCanRegisterToEvent()
    {
        $this->authenticateAsAdherent($this->client, 'benjyd@aol.com', 'HipHipHip');

        $eventUrl = '/action-citoyenne/'.date('Y-m-d', strtotime('+11 days')).'-projet-citoyen-paris-18';
        $crawler = $this->client->request('GET', $eventUrl);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('1 inscrit', trim($crawler->filter('.committee-event-attendees')->text()));

        $crawler = $this->client->click($crawler->selectLink('Je veux participer')->link());

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('Benjamin', $crawler->filter('#field-first-name > input[type="text"]')->attr('value'));
        $this->assertSame('13003', $crawler->filter('#field-postal-code > input[type="text"]')->attr('value'));
        $this->assertSame('benjyd@aol.com', $crawler->filter('#field-email-address > input[type="email"]')->attr('value'));

        $this->client->submit($crawler->selectButton("Je m'inscris")->form());

        $this->assertInstanceOf(EventRegistration::class, $this->repository->findGuestRegistration(LoadCitizenInitiativeData::CITIZEN_INITIATIVE_4_UUID, 'benjyd@aol.com'));
        $this->assertCount(1, $this->getEmailRepository()->findRecipientMessages(CitizenInitiativeRegistrationConfirmationMessage::class, 'benjyd@aol.com'));

        $crawler = $this->client->followRedirect();

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertTrue($this->seeMessageSuccessfullyCreatedFlash($crawler, 'Votre inscription est confirmée.'));
        $this->assertContains('Votre participation est bien enregistrée !', $crawler->filter('.committee-event-registration-confirmation p')->text());

        $crawler = $this->client->click($crawler->selectLink('Retour')->link());

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame('2 inscrits', trim($crawler->filter('.committee-event-attendees')->text()));

        $this->client->click($crawler->selectLink('Mes événements')->link());

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertContains('Nettoyage de la ville', $this->client->getResponse()->getContent());
    }

    public function testExportIcalAction(): void
    {
        $uuid = LoadCitizenActionData::CITIZEN_ACTION_3_UUID;
        /** @var CitizenAction $citizenAction */
        $citizenAction = $this->getRepository(CitizenAction::class)->findOneBy(['uuid' => $uuid]);

        $this->client->request(Request::METHOD_GET, sprintf('/action-citoyenne/%s/ical', $citizenAction->getSlug()));

        $this->isSuccessful($response = $this->client->getResponse());
        $this->assertSame(sprintf('attachment; filename=%s-projet-citoyen-3.ics', $citizenAction->getFinishAt()->format('Y-m-d')), $response->headers->get('Content-Disposition'));
        $this->assertSame('text/calendar; charset=UTF-8', $response->headers->get('Content-Type'));

        $beginAt = preg_quote($citizenAction->getBeginAt()->format('Ymd\THis'), '/');
        $finishAt = preg_quote($citizenAction->getFinishAt()->format('Ymd\THis'), '/');
        $uuid = preg_quote($uuid, '/');
        $icalRegex = <<<CONTENT
BEGIN\:VCALENDAR
VERSION\:2\.0
PRODID\:\-\/\/Sabre\/\/Sabre VObject 4\.1\.4\/\/EN
CALSCALE\:GREGORIAN
ORGANIZER\:CN\="Jacques PICARD"\\\\;mailto\:jacques\.picard@en\-marche\.fr
BEGIN\:VEVENT
UID\:$uuid
DTSTAMP\:\\d{8}T\\d{6}Z
SUMMARY\:Projet citoyen #3
DESCRIPTION\:Un troisième projet citoyen
DTSTART\:$beginAt
DTEND\:$finishAt
LOCATION\:16 rue de la Paix\\\\, 75008 Paris 8e
END\:VEVENT
END\:VCALENDAR
CONTENT;
        $icalRegex = str_replace("\n", "\r\n", $icalRegex); // Returned content contains CRLF

        $this->assertRegExp(sprintf('/%s/', $icalRegex), $response->getContent());
    }

    public function testUnregistrationSuccessful(): void
    {
        $this->authenticateAsAdherent($this->client, 'jacques.picard@en-marche.fr', 'changeme1337');

        $uuid = LoadCitizenActionData::CITIZEN_ACTION_3_UUID;
        /** @var CitizenAction $citizenAction */
        $citizenAction = $this->getRepository(CitizenAction::class)->findOneBy(['uuid' => $uuid]);

        $this->client->request(Request::METHOD_GET, sprintf('/action-citoyenne/%s', $citizenAction->getSlug()));

        $unregistrationButton = $this->client->getCrawler()->filter('#citizen_action-unregistration');

        $this->assertSame('Se désinscrire', trim($unregistrationButton->text()));
        $this->assertInstanceOf(EventRegistration::class, $this->getEventRegistrationRepository()->findAdherentRegistration($uuid, LoadAdherentData::ADHERENT_3_UUID));

        $this->client->request(Request::METHOD_POST, sprintf('/action-citoyenne/%s/desinscription', $citizenAction->getSlug()), [
            'token' => $unregistrationButton->attr('data-csrf-token'),
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, sprintf('/action-citoyenne/%s', $citizenAction->getSlug()));

        $this->assertSame('S\'inscrire', $this->client->getCrawler()->filter('a.newbtn--orange')->text());
        $this->assertNull($this->getEventRegistrationRepository()->findAdherentRegistration($uuid, LoadAdherentData::ADHERENT_3_UUID));
    }

    public function testUnregistrationFailed(): void
    {
        $this->markTestSkipped('Need to fix different token problem');

        $this->authenticateAsAdherent($this->client, 'carl999@example.fr', 'secret!12345');

        $this->client->disableReboot();
        $uuid = LoadCitizenActionData::CITIZEN_ACTION_4_UUID;
        /** @var CitizenAction $citizenAction */
        $citizenAction = $this->getRepository(CitizenAction::class)->findOneBy(['uuid' => $uuid]);

        $this->client->request(Request::METHOD_GET, sprintf('/action-citoyenne/%s', $citizenAction->getSlug()));

        $this->assertSame('S\'inscrire', $this->client->getCrawler()->filter('a.newbtn--orange')->text());
        $this->assertNull($this->getEventRegistrationRepository()->findAdherentRegistration($uuid, LoadAdherentData::ADHERENT_2_UUID));

        $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('citizen_action.unregistration');
        $this->client->request(Request::METHOD_POST, sprintf('/action-citoyenne/%s/desinscription', $citizenAction->getSlug()), [
            'token' => $csrfToken,
        ]);

        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, sprintf('/action-citoyenne/%s', $citizenAction->getSlug()));

        $this->assertSame('S\'inscrire', $this->client->getCrawler()->filter('a.newbtn--orange')->text());
        $this->assertNull($this->getEventRegistrationRepository()->findAdherentRegistration($uuid, LoadAdherentData::ADHERENT_2_UUID));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadAdherentData::class,
            LoadEventCategoryData::class,
            LoadCitizenProjectCategoryData::class,
            LoadCitizenProjectCategorySkillData::class,
            LoadCitizenProjectSkillData::class,
            LoadCitizenProjectData::class,
            LoadCitizenActionCategoryData::class,
            LoadCitizenActionData::class,
        ]);

        $this->repository = $this->getEventRegistrationRepository();
    }

    protected function tearDown()
    {
        $this->kill();

        $this->repository = null;

        parent::tearDown();
    }

    protected function getEventUrl(): string
    {
        return '/initiative-citoyenne/'.date('Y-m-d', strtotime('tomorrow')).'-apprenez-a-sauver-des-vies';
    }

    private function seeMessageSuccessfullyCreatedFlash(Crawler $crawler, ?string $message = null)
    {
        $flash = $crawler->filter('#notice-flashes');

        if ($message) {
            $this->assertSame($message, trim($flash->text()));
        }

        return 1 === count($flash);
    }
}
