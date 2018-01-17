<?php

namespace AppBundle\Producer;

use AppBundle\Entity\Adherent;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class CitizenProjectSummaryProducer extends Producer
{
    public function scheduleBroadcast(Adherent $adherent, string $approvedSince): void
    {
        $this->publish(\GuzzleHttp\json_encode([
            'adherent_uuid' => $adherent->getUuid(),
            'approved_since' => $approvedSince,
        ]));
    }
}
