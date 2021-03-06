<?php

namespace Tests\AppBundle\Controller\EnMarche;

use AppBundle\Search\SearchParametersFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\MysqlWebTestCase;

/**
 * @group functional
 */
class SearchControllerTest extends MysqlWebTestCase
{
    use ControllerTestTrait;

    /**
     * @dataProvider provideQuery
     */
    public function testIndex($query)
    {
        $this->client->request(Request::METHOD_GET, '/recherche', $query);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
    }

    /**
     * @dataProvider providerPathSearchPage
     */
    public function testAccessSearchPage(string $path)
    {
        $this->client->request(Request::METHOD_GET, $path);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
    }

    public function providerPathSearchPage()
    {
        return [
            ['/recherche/projets-citoyens'],
            ['/evenements'],
            ['/comites'],
            ['/recherche'],
        ];
    }

    public function provideQuery()
    {
        yield 'No criteria' => [[]];
        yield 'Search committees' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_COMMITTEES]];
        yield 'Search events' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_EVENTS]];
        yield 'Search citizen projects' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_CITIZEN_PROJECTS]];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init();
    }

    protected function tearDown()
    {
        $this->kill();

        parent::tearDown();
    }
}
