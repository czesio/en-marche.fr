<?php

namespace AppBundle\CitizenProject;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use AppBundle\Mailer\MailerService;
use AppBundle\Mailer\Message\CitizenProjectApprovedSummaryMessage;
use AppBundle\Mailer\Message\Message;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class CitizenProjectBroadcast
{
    private $mailer;
    private $twig;
    private $logger;

    public function __construct(MailerService $mailer, Environment $twig, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function handle(array $citizenProjects, Adherent $adherent): void
    {
        try {
            $broadcastedCitizenProjects = [];

            /** @var CitizenProject $citizenProject */
            foreach ($citizenProjects as $citizenProject) {
                array_push($broadcastedCitizenProjects, $citizenProject);
            }

            // Sends the message
            $message = $this->createMessage($broadcastedCitizenProjects, $adherent);
            $this->mailer->sendMessage($message);

            $this->logger->info(
                sprintf(
                    '[Broadcasting citizen projects] An email with %d CP has been queued for adherent #%d',
                    count($citizenProjects),
                    $adherent->getId()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[Broadcasting citizen projects] An error occurred while broadcasting',
                ['exception' => $e]
            );
        }
    }

    private function createMessage(array $broadcastedCitizenProjects, Adherent $adherent): Message
    {
        $summary = $this->twig->render(
            'citizen_project/_email_summary.html.twig', [
                'citizen_projects' => $broadcastedCitizenProjects,
            ]
        );

        return CitizenProjectApprovedSummaryMessage::create(
            $adherent,
            $summary
        );
    }
}
