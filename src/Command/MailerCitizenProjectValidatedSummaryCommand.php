<?php

namespace AppBundle\Command;

use AppBundle\Entity\Adherent;
use AppBundle\Producer\CitizenProjectSummaryProducer;
use AppBundle\Repository\AdherentRepository;
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

    /** @var AdherentRepository */
    private $adherentRepository;

    /** @var Logger */
    private $logger;

    /** @var CitizenProjectSummaryProducer */
    private $producer;

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
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                'Offset to start the query.',
                self::DEFAULT_OFFSET)
            ->setDescription('Sending a summary email of validated citizen projects to adherents.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->adherentRepository = $this->entityManager->getRepository(Adherent::class);

        $this->logger = $container->get('logger');
        $this->producer = $container->get('old_sound_rabbit_mq.citizen_project_summary_producer');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adherents = $this->adherentRepository->findAdherentsByCitizenProjectCreationEmailSubscription(
            $input->getOption('offset')
        );
        $adherentCount = 0;

        foreach ($adherents as $chunk) {
            /** @var Adherent $adherent */
            $adherent = $chunk[0];

            if (!$adherent->isEligibleToCitizenProjectBroadcast()) {
                continue;
            }

            $this->producer->scheduleBroadcast($adherent, $input->getOption('approved_since'));

            $this->adherentRepository->clear();
            ++$adherentCount;
        }

        $this->logger->info(sprintf('[Broadcasting citizen projects] Total adherents treated : %d', $adherentCount));
    }
}
