<?php

namespace App\Filament\Vendor\Resources\MyMessageResource\Pages;

use App\Filament\Vendor\Resources\MyMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListMyMessages extends ListRecords
{
    protected static string $resource = MyMessageResource::class;
}
