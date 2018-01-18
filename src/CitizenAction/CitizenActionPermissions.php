<?php

namespace AppBundle\CitizenAction;

final class CitizenActionPermissions
{
    public const CREATE = 'CREATE_CITIZEN_ACTION';
    public const EDIT = 'EDIT_CITIZEN_ACTION';
    public const UNREGISTER = 'UNREGISTER_CITIZEN_ACTION';

    public const MANAGE = [
        self::CREATE,
        self::EDIT,
    ];

    private function __construct()
    {
    }
}
