<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\OAuth\Client;
use AppBundle\OAuth\Model\GrantTypeEnum;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadClientData extends AbstractFixture implements FixtureInterface
{
    use ContainerAwareTrait;

    public const CLIENT_01_UUID = 'f80ce2df-af6d-4ce4-8239-04cfcefd5a19';
    public const CLIENT_02_UUID = '661cc3b7-322d-4441-a510-ab04eda71737';
    public const CLIENT_03_UUID = '4122f4ce-f994-45f7-9ff5-f9f09ab3991e';

    public function load(ObjectManager $manager)
    {
        $client1 = new Client(
            Uuid::fromString(self::CLIENT_01_UUID),
            'En-Marche !',
            'Plateforme Citoyenne de la République En-Marche !',
            '2x26pszrpag408so88w4wwo4gs8o8ok4osskcw00ow80sgkkcs',
            [GrantTypeEnum::AUTHORIZATION_CODE, GrantTypeEnum::REFRESH_TOKEN],
            ['http://client-oauth.docker:8000/client/receive_authcode', 'https://en-marche.fr/callback']
        );
        $client1->addSupportedScope('user_profile');
        $manager->persist($client1);

        $client2 = new Client(
            Uuid::fromString(self::CLIENT_02_UUID),
            'En-Marche (avec by pass auth) !',
            'Plateforme Citoyenne de la République En-Marche !',
            'y866p4gbcbrsl84ptnhas7751iw3on319983a13e6y862tb9c2',
            [GrantTypeEnum::AUTHORIZATION_CODE, GrantTypeEnum::REFRESH_TOKEN],
            ['http://client-oauth.docker:8000/client/receive_authcode']
        );
        $client2->addSupportedScope('user_profile');
        $client2->setAskUserForAuthorization(false);
        $manager->persist($client2);

        $client3 = new Client(
            Uuid::fromString(self::CLIENT_03_UUID),
            'En-Marche API !',
            'Plateforme Citoyenne de la République En-Marche !',
            'dALH/khq9BcjOS0GB6u5NaJ3R9k2yvSBq5wYUHx1omA=',
            [GrantTypeEnum::CLIENT_CREDENTIALS]
        );
        $client3->addSupportedScope('public');
        $manager->persist($client3);

        $manager->flush();
    }
}
