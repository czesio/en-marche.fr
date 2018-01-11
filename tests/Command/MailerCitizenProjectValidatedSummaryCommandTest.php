<?php

namespace AppBundle\Command;

use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectData;
use AppBundle\Mailer\Message\CitizenProjectApprovedSummaryMessage;
use Tests\AppBundle\MysqlWebTestCase;
use Tests\AppBundle\TestHelperTrait;

/**
 * @group functional
 */
class MailerCitizenProjectValidatedSummaryCommandTest extends MysqlWebTestCase
{
    use TestHelperTrait;

    /**
     * @dataProvider broadcastedProvider
     */
    public function testBroadcastCitizenProjects($approvedSince, $offset, $sentEmails)
    {
        $this->runCommand(
            MailerCitizenProjectValidatedSummaryCommand::COMMAND_NAME,
            ['--approvedSince' => $approvedSince, '--offset' => $offset]
        );

        $this->assertCountMails($sentEmails, CitizenProjectApprovedSummaryMessage::class);
    }

    public function broadcastedProvider()
    {
        return [
            ['7 days', null, 1],
            ['6 months', null, 6],
            ['7 days', 10, 0],
            ['6 months', 5, 2],
            [null, null, 6],
        ];
    }

    public function setUp()
    {
        $this->container = $this->getContainer();

        $this->loadFixtures([
            LoadAdherentData::class,
            LoadCitizenProjectData::class,
        ]);

        parent::setUp();
    }

    public function tearDown()
    {
        $this->container = null;

        $this->loadFixtures([]);

        parent::tearDown();
    }
}
