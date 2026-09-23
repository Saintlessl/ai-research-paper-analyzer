<?php

namespace App\Enums;

enum RoleName: string
{
    case Researcher = 'researcher';
    case Reviewer = 'reviewer';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
}
