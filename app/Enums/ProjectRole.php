<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Member => 'Member',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Roles allowed to edit the project itself (not just its tasks).
     * Used by ProjectPolicy::update() — a Member or Viewer can be part of
     * a project without being allowed to rename it or change its dates.
     */
    public function canManageProject(): bool
    {
        return match ($this) {
            self::Owner, self::Manager => true,
            self::Member, self::Viewer => false,
        };
    }
}
