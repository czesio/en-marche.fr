<?php

namespace AppBundle\Security\Voter\CitizenProject;

use AppBundle\CitizenProject\CitizenProjectPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class CommentsCitizenProjectVoter extends AbstractAdherentVoter
{
    protected function supports($attribute, $citizenProject)
    {
        return in_array($attribute, CitizenProjectPermissions::COMMENTS, true)
            && $citizenProject instanceof CitizenProject
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
        return (bool) $adherent->getCitizenProjectMembershipFor($citizenProject);
    }
}
