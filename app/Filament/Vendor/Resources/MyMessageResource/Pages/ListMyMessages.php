<?php

namespace App\Filament\Vendor\Resources\MyMessageResource\Pages;

use App\Filament\Vendor\Resources\MyMessageResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListMyMessages extends ListRecords
{
    protected static string $resource = MyMessageResource::class;

    public function getTitle(): string | Htmlable
    {
        return 'Client chat';
    }
}
