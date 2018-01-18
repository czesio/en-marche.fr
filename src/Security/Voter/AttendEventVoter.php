<?php

namespace AppBundle\Security\Voter;

use AppBundle\CitizenAction\CitizenActionPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\BaseEvent;
use AppBundle\Entity\CitizenAction;
use AppBundle\Entity\Event;
use AppBundle\Event\EventPermissions;
use AppBundle\Repository\EventRegistrationRepository;

class AttendEventVoter extends AbstractAdherentVoter
{
    private $registrationRepository;

    public function __construct(EventRegistrationRepository $registrationRepository)
    {
        $this->registrationRepository = $registrationRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return EventPermissions::UNREGISTER === $attribute && $subject instanceof Event
            || CitizenActionPermissions::UNREGISTER === $attribute && $subject instanceof CitizenAction
        ;
    }

    /**
     * @param string    $attribute
     * @param Adherent  $adherent
     * @param BaseEvent $subject
     *
     * @return bool
     */
    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $subject): bool
    {
        return (bool) $this->registrationRepository->findByRegisteredEmailAndEvent(
            $adherent->getEmailAddress(),
            $subject
        );
    }
}
