<?php

namespace AppBundle\Command;

use AppBundle\CitizenProject\CitizenProjectBroadcaster;
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

    /** @var EntityManager */
    private $entityManager;

    /** @var Logger */
    private $logger;

    /** @var CitizenProjectBroadcaster */
    private $broadcaster;

    /** @var AdherentRepository */
    private $adherentRepository;

    /** @var CitizenProjectRepository */
    private $citizenProjectRepository;

    protected function configure()
    {
        $this
          ->setName(self::COMMAND_NAME)
          ->addOption(
              'approved_since',
              null,
              InputOption::VALUE_OPTIONAL,
              'Duration in format Time since the citizen projects has been approved.',
              self::DEFAULT_APPROVED_SINCE
          )
          ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset to start the query.', self::DEFAULT_OFFSET)
          ->setDescription('Sending a summary email of validated citizen projects to adherents.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->logger = $container->get('logger');
        $this->broadcaster = $container->get(CitizenProjectBroadcaster::class);

        $this->adherentRepository = $this->entityManager->getRepository(Adherent::class);
        $this->citizenProjectRepository = $this->entityManager->getRepository(CitizenProject::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adherents = $this->adherentRepository->findAdherentsByCitizenProjectCreationEmailSubscription(
            $input->getOption('offset')
        );
        $adherentCount = 0;

        try {
            foreach ($adherents as $chunk) {
                /** @var Adherent $adherent */
                $adherent = $chunk[0];

                if (!$adherent->isEligibleToCitizenProjectBroadcast()) {
                    continue;
                }

                // Broadcast the citizen projects
                $this->broadcaster->broadcast($adherent, $input->getOption('approved_since'));
                ++$adherentCount;
            }
        } catch (\Exception $e) {
            $this->logger->info(
                sprintf(
                    '[Broadcasting citizen projects] [Error] Treating adherent... (offset: %d)',
                    $adherentCount
                )
            );
        }

        $this->logger->info(sprintf('[Broadcasting citizen projects] Total adherents treated : %d', $adherentCount));
    }
}
