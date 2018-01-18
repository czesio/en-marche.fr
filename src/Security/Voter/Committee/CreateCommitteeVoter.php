<?php

namespace AppBundle\Security\Voter\Committee;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Committee\CommitteePermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Security\Voter\AbstractAdherentVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CreateCommitteeVoter extends AbstractAdherentVoter
{
    private $manager;

    public function __construct(CommitteeManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $subject)
    {
        return CommitteePermissions::CREATE === $attribute && null === $subject;
    }

    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $subject): bool
    {
        return $adherent->isReferent()
            && !$this->manager->isSupervisorOfAnyCommittee($adherent)
            && !$this->manager->isCommitteeHost($adherent)
            && !$this->manager->hasCommitteeInStatus($adherent, CommitteeManager::COMMITTEE_STATUS_NOT_ALLOWED_TO_CREATE_ANOTHER)
        ;
    }
}
