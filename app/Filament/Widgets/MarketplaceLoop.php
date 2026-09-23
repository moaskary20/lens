<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\DeliverableResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VendorResource;
use App\Models\Booking;
use App\Models\Deliverable;
use App\Models\User;
use App\Models\Vendor;
use Filament\Widgets\Widget;

class MarketplaceLoop extends Widget
{
    protected string $view = 'filament.widgets.marketplace-loop';

    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'steps' => [
                [
                    'num' => '01',
                    'title' => 'Clients',
                    'count' => User::query()->where('role', 'client')->count(),
                    'unit' => 'users',
                    'text' => 'A client browses the marketplace, books a vendor, and opens a project with a brief and reference files.',
                    'url' => UserResource::getUrl('index'),
                ],
                [
                    'num' => '02',
                    'title' => 'Vendors',
                    'count' => Vendor::query()->count(),
                    'unit' => 'listed',
                    'text' => 'A vendor lists under a type such as photographer. They upload previous work and set hours plus prices on their own profile.',
                    'url' => VendorResource::getUrl('index'),
                ],
                [
                    'num' => '03',
                    'title' => 'Projects',
                    'count' => Booking::query()->count(),
                    'unit' => 'bookings',
                    'text' => 'Each booking is the client project: category, date, escrow payment, brief, and files the vendor works from.',
                    'url' => BookingResource::getUrl('index'),
                ],
                [
                    'num' => '04',
                    'title' => 'Delivery',
                    'count' => Deliverable::query()->count(),
                    'unit' => 'files',
                    'text' => 'The vendor uploads finished files on Lens. The client previews, requests edits, then approves and downloads.',
                    'url' => DeliverableResource::getUrl('index'),
                ],
            ],
        ];
    }
}
