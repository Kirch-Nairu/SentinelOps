<?php

namespace App\Domain\Shared;

enum Role: string
{
    case Administrator = 'administrator';
    case Supervisor = 'supervisor';
    case Technician = 'technician';
    case SecurityOfficer = 'security_officer';
    case Auditor = 'auditor';
}
