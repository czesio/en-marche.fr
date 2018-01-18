<?php

namespace AppBundle\Security\Voter\CitizenProject;

use AppBundle\CitizenProject\CitizenProjectPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class FollowerCitizenProjectVoter extends AbstractAdherentVoter
{
    protected function supports($attribute, $subject)
    {
        return in_array(strtoupper($attribute), CitizenProjectPermissions::FOLLOWER, true)
            && $subject instanceof CitizenProject
        ;
    }

    /**
     * @param string         $attribute
     * @param Adherent       $adherent
     * @param CitizenProject $citizenProject
     *
     * @return bool
     */
    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $citizenProject): bool
    {
        if (!$citizenProject->isApproved()) {
            return false;
        }

        $membership = $adherent->getCitizenProjectMembershipFor($citizenProject);

        if (CitizenProjectPermissions::FOLLOW === $attribute) {
            return !$membership;
        }

        // Administrator cannot unfollow (or leave)
        if (!$membership || $membership->isAdministrator()) {
            return false;
        }

        return $membership->isFollower();
    }
}
