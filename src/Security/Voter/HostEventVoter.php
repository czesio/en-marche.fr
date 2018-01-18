<?php

namespace AppBundle\Security\Voter;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\Event;
use AppBundle\Event\EventPermissions;
use AppBundle\Repository\CommitteeMembershipRepository;

class HostEventVoter extends AbstractAdherentVoter
{
    private $repository;

    public function __construct(CommitteeMembershipRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function supports($attribute, $event)
    {
        return EventPermissions::HOST === $attribute && $event instanceof Event;
    }

    /**
     * @param string   $attribute
     * @param Adherent $adherent
     * @param Event    $event
     *
     * @return bool
     */
    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $event): bool
    {
        if ($event->getOrganizer() && $adherent->equals($event->getOrganizer())) {
            return true;
        }

        if (!$committee = $event->getCommittee()) {
            return false;
        }

        // Optimization to prevent a SQL query if the current adherent already
        // has a loaded list of related committee memberships entities.
        if ($adherent->hasLoadedMemberships()) {
            return $adherent->isHostOf($committee);
        }

        return $this->repository->hostCommittee($adherent, $committee->getUuid());
    }
}
