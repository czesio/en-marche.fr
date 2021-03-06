<?php

namespace AppBundle\Search;

use AppBundle\Geocoder\Coordinates;
use AppBundle\Geocoder\Exception\GeocodingException;
use AppBundle\Geocoder\GeocoderInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a way to handle the search parameters.
 */
class SearchParametersFilter
{
    const CACHE_KEY_PREFIX = 'search_geocoding_city_';

    const PARAMETER_QUERY = 'q';
    const PARAMETER_RADIUS = 'r';
    const PARAMETER_CITY = 'c';
    const PARAMETER_TYPE = 't';
    const PARAMETER_OFFSET = 'offset';
    const PARAMETER_EVENT_CATEGORY = 'ec';

    const DEFAULT_QUERY = '';
    const DEFAULT_TYPE = self::TYPE_COMMITTEES;
    const DEFAULT_RADIUS = self::RADIUS_50;
    const DEFAULT_CITY = 'Paris';
    const DEFAULT_MAX_RESULTS = 30;

    const TYPE_COMMITTEES = 'committees';
    const TYPE_EVENTS = 'events';
    const TYPE_CITIZEN_PROJECTS = 'citizen_projects';
    const TYPE_CITIZEN_ACTIONS = 'citizen_actions';

    const TYPES = [
        self::TYPE_COMMITTEES,
        self::TYPE_EVENTS,
        self::TYPE_CITIZEN_PROJECTS,
        self::TYPE_CITIZEN_ACTIONS,
    ];

    const RADIUS_1 = 1;
    const RADIUS_5 = 5;
    const RADIUS_10 = 10;
    const RADIUS_25 = 25;
    const RADIUS_50 = 50;
    const RADIUS_100 = 100;
    const RADIUS_150 = 150;

    const RADII = [
        self::RADIUS_1,
        self::RADIUS_5,
        self::RADIUS_10,
        self::RADIUS_25,
        self::RADIUS_50,
        self::RADIUS_100,
        self::RADIUS_150,
    ];

    private $geocoder;
    private $query;
    private $type;
    private $radius;
    private $city;
    private $offset;
    private $maxResults;
    private $eventCategory;

    public function __construct(GeocoderInterface $geocoder, AdapterInterface $cache)
    {
        $this->geocoder = $geocoder;
        $this->cache = $cache;
        $this->query = self::DEFAULT_QUERY;
        $this->type = self::DEFAULT_TYPE;
        $this->radius = self::DEFAULT_RADIUS;
        $this->city = self::DEFAULT_CITY;
        $this->offset = 0;
        $this->maxResults = self::DEFAULT_MAX_RESULTS;
    }

    /**
     * @param Request $request
     *
     * @return SearchParametersFilter
     */
    public function handleRequest(Request $request): self
    {
        $this->setQuery((string) $request->query->get(self::PARAMETER_QUERY));
        $this->setType($request->query->get(self::PARAMETER_TYPE, ''));
        $this->setRadius($request->query->getInt(self::PARAMETER_RADIUS));
        $this->setOffset($request->query->getInt(self::PARAMETER_OFFSET));
        $this->setEventCategory($request->query->getAlnum(self::PARAMETER_EVENT_CATEGORY, null));

        if (null !== $city = $request->query->get(self::PARAMETER_CITY)) {
            $this->setCity((string) $city);
        }

        return $this;
    }

    public function setType(string $type): self
    {
        // Will fallback to the default one
        $this->type = in_array($type, self::TYPES, true) ? $type : self::DEFAULT_TYPE;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setRadius(int $radius): self
    {
        // We fallback to the default one
        $this->radius = in_array($radius, self::RADII, true) ? $radius : self::DEFAULT_RADIUS;

        return $this;
    }

    public function getRadius(): int
    {
        return $this->radius;
    }

    public function setCity(string $city): self
    {
        $this->city = trim($city);

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    /**
     * @throws GeocodingException
     */
    public function getCityCoordinates(): ?Coordinates
    {
        $id = self::CACHE_KEY_PREFIX.md5(strtolower($this->city));
        $item = $this->cache->getItem($id);

        if ($item->isHit() && $item->get() instanceof Coordinates) {
            return $item->get();
        }

        $item->set($coordinates = $this->geocoder->geocode($this->city));
        $this->cache->save($item);

        return $coordinates;
    }

    public function setQuery(string $query): self
    {
        $this->query = trim($query);

        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset < 0 ? 0 : $offset;

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setMaxResults(int $maxResults): self
    {
        $this->maxResults = $maxResults < 1 ? self::DEFAULT_MAX_RESULTS : $maxResults;

        return $this;
    }

    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function getEventCategory(): ?string
    {
        return $this->eventCategory;
    }

    public function setEventCategory(?string $eventCategory)
    {
        $this->eventCategory = $eventCategory;
    }

    public function isTypeCommittees(): bool
    {
        return self::TYPE_COMMITTEES === $this->getType();
    }

    public function isTypeEvents(): bool
    {
        return self::TYPE_EVENTS === $this->getType();
    }

    public function isTypeCitizenProjects(): bool
    {
        return self::TYPE_CITIZEN_PROJECTS === $this->getType();
    }
}
