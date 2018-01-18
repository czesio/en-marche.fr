<?php

namespace AppBundle\Security\Voter\Committee;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Committee\CommitteePermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Committee;
use AppBundle\Security\Voter\AbstractAdherentVoter;

class HostCommitteeVoter extends AbstractAdherentVoter
{
    private $manager;

    public function __construct(CommitteeManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $committee)
    {
        return CommitteePermissions::HOST === $attribute && $committee instanceof Committee;
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
            return $this->manager->superviseCommittee($adherent, $committee)
                || $committee->isCreatedBy($adherent->getUuid())
            ;
        }

        return $this->manager->hostCommittee($adherent, $committee);
    }
}
