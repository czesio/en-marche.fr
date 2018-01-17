<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

final class CitizenProjectApprovedSummaryMessage extends Message
{
    public static function create(Adherent $adherent, string $summary): self
    {
        $message = new self(
            Uuid::uuid4(),
            '289976',
            $adherent->getEmailAddress(),
            $adherent->getFullName(),
            '[Projets citoyens] Les projets citoyens près de chez vous !',
            [
                'target_firstname' => self::escape($adherent->getFirstName()),
                'citizen_project_list' => self::escape($summary),
            ]
        );

        $message->setSenderEmail('projetscitoyens@en-marche.fr');

        return $message;
    }
}
