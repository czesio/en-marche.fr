<?php

namespace AppBundle\Security\Voter\Committee;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Committee\CommitteePermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Committee;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class SuperviseCommitteeVoter extends AbstractAdherentVoter
{
    private $manager;

    public function __construct(CommitteeManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $subject): bool
    {
        return CommitteePermissions::SUPERVISE === $attribute && $subject instanceof Committee;
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
        return $this->manager->superviseCommittee($adherent, $committee);
    }
}
