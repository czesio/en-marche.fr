<?php

namespace AppBundle\Repository;

use AppBundle\Coordinator\Filter\CitizenProjectFilter;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\BaseGroup;
use AppBundle\Entity\CitizenProject;
use AppBundle\Geocoder\Coordinates;
use AppBundle\Search\SearchParametersFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\UuidInterface;

class CitizenProjectRepository extends BaseGroupRepository
{
    use NearbyTrait;

    /**
     * Returns the total number of approved citizen projects.
     *
     * @return int
     */
    public function countApprovedCitizenProjects(): int
    {
        return $this
            ->createQueryBuilder('g')
            ->select('COUNT(g.uuid)')
            ->where('g.status = :status')
            ->setParameter('status', BaseGroup::APPROVED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findCitizenProjects(array $uuids, int $statusFilter = self::ONLY_APPROVED, int $limit = 0): ArrayCollection
    {
        if (!$uuids) {
            return new ArrayCollection();
        }

        $statuses[] = BaseGroup::APPROVED;
        if (self::INCLUDE_UNAPPROVED === $statusFilter) {
            $statuses[] = BaseGroup::PENDING;
        }

        $qb = $this->createQueryBuilder('c');

        $qb
            ->where($qb->expr()->in('c.uuid', $uuids))
            ->andWhere($qb->expr()->in('c.status', $statuses))
            ->orderBy('c.membersCounts', 'DESC')
        ;

        if ($limit >= 1) {
            $qb->setMaxResults($limit);
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    public function findOneApprovedBySlug(string $slug): ?CitizenProject
    {
        return $this
            ->createQueryBuilder('g')
            ->where('g.slug = :slug')
            ->andWhere('g.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', BaseGroup::APPROVED)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findManagedByCoordinator(Adherent $coordinator, CitizenProjectFilter $filter): array
    {
        if (!$coordinator->isCoordinatorCitizenProjectSector()) {
            return [];
        }

        $qb = $this->createQueryBuilder('cp')
            ->orderBy('cp.name', 'ASC')
            ->orderBy('cp.createdAt', 'DESC');

        $filter->setCoordinator($coordinator);
        $filter->apply($qb, 'cp');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return CitizenProject[]
     */
    public function searchAll(SearchParametersFilter $search): iterable
    {
        if (SearchParametersFilter::TYPE_CITIZEN_PROJECTS !== $search->getType()) {
            throw new \LogicException(sprintf('Only %s is supported', SearchParametersFilter::TYPE_CITIZEN_PROJECTS));
        }

        if ($coordinates = $search->getCityCoordinates()) {
            $qb = $this
                ->createNearbyQueryBuilder($coordinates)
                ->andWhere($this->getNearbyExpression().' < :distance_max')
                ->setParameter('distance_max', $search->getRadius());
        } else {
            $qb = $this->createQueryBuilder('n');
        }

        if (!empty($query = $search->getQuery())) {
            $qb->andWhere('n.name like :query');
            $qb->setParameter('query', "%$query%");
        }

        return $qb
            ->andWhere('n.status = :status')
            ->setParameter('status', CitizenProject::APPROVED)
            ->setFirstResult($search->getOffset())
            ->setMaxResults($search->getMaxResults())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Finds all citizen projects around an Adherent.
     *
     * @param Adherent $adherent
     * @param int      $limit         Limit of number of citizen projects to return
     * @param int      $approvedSince Duration in format Time since the citizen projects have been approved
     *
     * @return CitizenProject[]
     */
    public function findNearByCitizenProjectsForAdherent(
        Adherent $adherent, int $limit, ?string $approvedSince
    ): array {
        $qb = $this
            ->createNearbyQueryBuilder(new Coordinates($adherent->getLatitude(), $adherent->getLongitude()))
            ->andWhere($this->getNearbyExpression().' <= :distance_max')
            ->setParameter('distance_max', $adherent->getCitizenProjectCreationEmailSubscriptionRadius());

        if (null !== $approvedSince && false !== strtotime($approvedSince)) {
            $dateMin = new \DateTime('now');
            $dateMin->modify("-$approvedSince");

            $qb
                ->andWhere('n.approvedAt >= :dateMin')
                ->setParameter('dateMin', $dateMin->format('Y-m-d'))
            ;
        }

        return $qb
            ->andWhere('n.status = :status')
            ->setParameter('status', CitizenProject::APPROVED)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
         ;
    }

    public function hasCitizenProjectInStatus(Adherent $adherent, array $status): bool
    {
        $nb = $this->createQueryBuilder('cp')
            ->select('COUNT(cp) AS nb')
            ->where('cp.createdBy = :creator')
            ->andWhere('cp.status IN (:status)')
            ->setParameter('creator', $adherent->getUuid()->toString())
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return $nb > 0;
    }

    public function findCitizenProjectUuidByCreatorUuids(array $creatorsUuid): array
    {
        $qb = $this->createQueryBuilder('cp');

        $query = $qb
            ->select('cp.uuid')
            ->where('cp.createdBy IN (:creatorsUuid)')
            ->setParameter('creatorsUuid', $creatorsUuid)
            ->getQuery()
        ;

        return array_map(function (UuidInterface $uuid) {
            return $uuid->toString();
        }, array_column($query->getArrayResult(), 'uuid'));
    }

    public function findNearCitizenProjectByCoordinates(Coordinates $coordinates, int $limit = 3): array
    {
        return $this
            ->createNearbyQueryBuilder($coordinates)
            ->where('n.status = :status')
            ->setParameter('status', BaseGroup::APPROVED)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
