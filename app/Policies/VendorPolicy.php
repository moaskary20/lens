<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->isStaff() || $user->ownsVendor($vendor);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->isStaff() || $user->ownsVendor($vendor);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin();
    }
}
