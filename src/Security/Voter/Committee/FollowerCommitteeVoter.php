<?php

namespace AppBundle\Security\Voter\Committee;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Committee\CommitteePermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Committee;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class FollowerCommitteeVoter extends AbstractAdherentVoter
{
    private $manager;

    public function __construct(CommitteeManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $subject)
    {
        return $subject instanceof Committee
            && in_array($attribute, CommitteePermissions::FOLLOWER, true)
        ;
    }

    /**
     * @param string    $attribute
     * @param Adherent  $adherent
     * @param Committee $committee
     *
     * @return bool
     */
    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $committee): bool
    {
        if (!$committee->isApproved()) {
            return false;
        }

        $membership = $this->manager->getCommitteeMembership($adherent, $committee);

        if (CommitteePermissions::FOLLOW === $attribute) {
            return !$membership;
        }

        // A supervisor cannot unfollow its committee
        if (!$membership || $membership->isSupervisor()) {
            return false;
        }

        // Any basic follower of a committee can unfollow the committee
        // at any point in time.
        return $membership->isFollower() || 1 < $this->manager->countCommitteeHosts($committee);
    }
}
