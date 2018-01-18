<?php

namespace AppBundle\Security\Voter\CitizenProject;

use AppBundle\CitizenProject\CitizenProjectPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use AppBundle\Repository\CitizenProjectRepository;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class CreateCitizenProjectVoter extends AbstractAdherentVoter
{
    private $projectRepository;

    public function __construct(CitizenProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    protected function supports($attribute, $subject)
    {
        return CitizenProjectPermissions::CREATE === $attribute && null === $subject;
    }

    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $subject): bool
    {
        // Cannot create more than one
        if ($adherent->isAdministrator()) {
            return false;
        }

        return !$this->projectRepository->hasCitizenProjectInStatus($adherent, CitizenProject::STATUS_NOT_ALLOWED_TO_CREATE);
    }
}
