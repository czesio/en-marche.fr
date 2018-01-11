<?php

namespace AppBundle\Command;

use AppBundle\CitizenProject\CitizenProjectBroadcast;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use AppBundle\Repository\AdherentRepository;
use AppBundle\Repository\CitizenProjectRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailerCitizenProjectValidatedSummaryCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'app:mailer:citizen-project-summary';
    private const DEFAULT_APPROVED_SINCE = '7 days';
    private const DEFAULT_OFFSET = 0;
    private const CITIZEN_PROJECTS_SUMMARY_LIMIT = 15;

    /** @var EntityManager */
    private $entityManager;

    /** @var Logger */
    private $logger;

    /** @var CitizenProjectBroadcast */
    private $broadcast;

    /** @var AdherentRepository */
    private $adherentRepository;

    /** @var CitizenProjectRepository */
    private $citizenProjectRepository;

    protected function configure()
    {
        $this
          ->setName(self::COMMAND_NAME)
          ->addOption(
              'approvedSince',
              'as',
              InputOption::VALUE_OPTIONAL,
              'Duration in format Time since the citizen projects has been approved.',
              self::DEFAULT_APPROVED_SINCE
          )
          ->addOption('offset', 'of', InputOption::VALUE_OPTIONAL, 'Offset to start the query.', self::DEFAULT_OFFSET)
          ->setDescription('Sending a summary email of validated citizen projects to adherents.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->logger = $container->get('logger');
        $this->broadcast = $container->get(CitizenProjectBroadcast::class);

        $this->adherentRepository = $this->entityManager->getRepository(Adherent::class);
        $this->citizenProjectRepository = $this->entityManager->getRepository(CitizenProject::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adherents = $this->adherentRepository->findAdherentsByCitizenProjectCreationEmailSubscription(
            $input->getOption('offset')
        );

        $adherentCount = 0;
        $emailCount = 0;

        foreach ($adherents as $chunk) {
            ++$adherentCount;

            /** @var Adherent $adherent */
            $adherent = $chunk[0];
            $this->logger->info(
                sprintf(
                    '[Broadcasting citizen projects] Treating adherent... (id #%d) (offset: %d)',
                    $adherent->getId(),
                    $adherentCount
                )
            );

            // Finds all citizen projects near the Adherent
            $citizenProjects = $this->citizenProjectRepository->findNearByCitizenProjectSummariesForAdherent(
                $adherent,
                self::CITIZEN_PROJECTS_SUMMARY_LIMIT,
                $input->getOption('approvedSince')
            );

            if (0 === count($citizenProjects)) {
                continue;
            }

            // Broadcast the citizen projects
            $this->broadcast->handle($citizenProjects, $adherent);

            $this->entityManager->clear(CitizenProject::class);
            ++$emailCount;
        }

        $this->logger->info(sprintf('[Broadcasting citizen projects] Total emails queued : %d', $emailCount));
        $this->logger->info(sprintf('[Broadcasting citizen projects] Total adherents treated : %d', $adherentCount));
    }
}
