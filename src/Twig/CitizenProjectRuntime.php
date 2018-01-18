<?php

namespace AppBundle\Twig;

use AppBundle\CitizenProject\CitizenProjectManager;
use AppBundle\CitizenProject\CitizenProjectPermissions;
use AppBundle\CitizenProject\CitizenProjectAuthority;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenProject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CitizenProjectRuntime
{
    private const COLOR_STATUS_PENDING = 'bullet--pending';
    private const COLOR_STATUS_ADMINISTRATOR = 'bullet--own';

    private $authorizationChecker;
    private $projectAuthority;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        CitizenProjectManager $citizenProjectManager,
        CitizenProjectAuthority $projectAuthority
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->projectAuthority = $projectAuthority;
    }

    public function isPromotableAdministrator(Adherent $adherent, CitizenProject $citizenProject): bool
    {
        return $this->projectAuthority->isPromotableAdministrator($adherent, $citizenProject);
    }

    public function isDemotableAdministrator(Adherent $adherent, CitizenProject $citizenProject): bool
    {
        return $this->projectAuthority->isDemotableAdministrator($adherent, $citizenProject);
    }

    public function isAdministrator(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::ADMINISTRATE, $citizenProject);
    }

    public function canFollowCitizenProject(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::FOLLOW, $citizenProject);
    }

    public function canUnfollowCitizenProject(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::UNFOLLOW, $citizenProject);
    }

    public function canSeeCitizenProject(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::SHOW, $citizenProject);
    }

    public function canCommentCitizenProject(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::COMMENT, $citizenProject);
    }

    public function canSeeCommentCitizenProject(CitizenProject $citizenProject): bool
    {
        return $this->authorizationChecker->isGranted(CitizenProjectPermissions::SHOW_COMMENT, $citizenProject);
    }

    public function getCitizenProjectColorStatus(Adherent $adherent, CitizenProject $citizenProject): string
    {
        if ($citizenProject->isPending()) {
            return self::COLOR_STATUS_PENDING;
        }

        if ($adherent->isAdministratorOf($citizenProject)) {
            return self::COLOR_STATUS_ADMINISTRATOR;
        }

        return '';
    }
}
