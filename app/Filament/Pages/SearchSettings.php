<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Feature;
use App\Support\Roles;
use App\Support\SearchEngine;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SearchSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Search engine';

    protected static ?string $title = 'Search architecture';

    protected static string|UnitEnum|null $navigationGroup = 'Search Engine';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings')
            && (Feature::enabled('filters') || Feature::enabled('ai_assistant'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(array_merge(SearchEngine::defaults(), Setting::groupValues('search')));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('AI Smart Assistant')
                    ->description('Gemini chat for the website and app. Asks location, then date, then budget, compares vendors, and helps the client pick. Falls back to the catalog lexicon when no API key is set.')
                    ->schema([
                        Toggle::make('ai_enabled')->label('Enable AI matching')->helperText('Uses Gemini when a key is configured. Also requires the AI assistant feature flag.'),
                        Toggle::make('voice_input_enabled')->label('Allow voice input'),
                        TextInput::make('ai_prompt')->label('Client prompt')->placeholder('What will you create today?'),
                        TextInput::make('gemini_model')->label('Gemini model')->placeholder('gemini-2.5-flash')
                            ->helperText('Google AI Studio model id. Default gemini-2.5-flash.'),
                        TextInput::make('gemini_api_key')->label('Gemini API key')->password()->revealable()
                            ->helperText('Leave blank to use GEMINI_API_KEY from the server .env. Get a key from Google AI Studio.')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        Textarea::make('ai_system_prompt')->label('System instructions')->rows(3)->columnSpanFull(),
                        Select::make('moodboard_mode')->label('Moodboard')->options([
                            'off' => 'Disabled',
                            'after_payment' => 'Generate after payment',
                            'always' => 'Generate from the brief immediately',
                        ])->helperText('Spec default: appear after the client pays.'),
                    ])->columns(2),
                Section::make('Primary search facets')
                    ->description('Always-on discovery controls for every search.')
                    ->schema([
                        Toggle::make('primary_category')->label('Category (F&B, Wedding, Events, Product, Photoshoot, Corporate)'),
                        Toggle::make('primary_creator_type')->label('Creator type (photographer, studio, model, …)'),
                        Toggle::make('primary_location')->label('Location & geo radius'),
                        Toggle::make('primary_availability')->label('Date & time availability'),
                        Toggle::make('primary_price')->label('Price range & package tiers'),
                    ])->columns(2),
                Section::make('Location & map')->schema([
                    Toggle::make('map_enabled')->label('Interactive mini-map')->helperText('Also requires the Map feature flag.'),
                    TextInput::make('geo_radii')->label('Distance presets (km)')
                        ->helperText('Comma-separated. Spec default: 5, 10, 25.'),
                ])->columns(2),
                Section::make('Date, budget & ranking')->schema([
                    Toggle::make('availability_today')->label('Available today'),
                    Toggle::make('availability_weekend')->label('Available this weekend'),
                    Toggle::make('availability_specific')->label('Specific date & time'),
                    Toggle::make('budget_session_slider')->label('Session price min / max'),
                    Toggle::make('budget_package_filter')->label('Full package prices'),
                    TextInput::make('top_rated_threshold')->label('Top rated threshold')->numeric()->step(0.1),
                    Toggle::make('rank_verified')->label('Verified badge filter'),
                    Toggle::make('rank_fast_replies')->label('Fast replies filter'),
                    Toggle::make('rank_completed_sessions')->label('Successful sessions count'),
                    Toggle::make('rank_reviews')->label('Client star ratings in ranking'),
                    Toggle::make('rank_featured')->label('Boost featured placements'),
                    Toggle::make('rank_badges')->label('Boost Top Rated / Popular badges'),
                ])->columns(2),
                Section::make('Matching weights')
                    ->description('How the engine scores vendors when the AI or ranked search runs. Total does not need to be 100.')
                    ->schema([
                        TextInput::make('weight_category')->label('Category')->numeric(),
                        TextInput::make('weight_vendor_type')->label('Creator type')->numeric(),
                        TextInput::make('weight_location')->label('Location')->numeric(),
                        TextInput::make('weight_availability')->label('Availability')->numeric(),
                        TextInput::make('weight_tags')->label('Equipment / features')->numeric(),
                        TextInput::make('weight_rating')->label('Rating')->numeric(),
                        TextInput::make('weight_completed_sessions')->label('Completed sessions')->numeric(),
                        TextInput::make('weight_featured')->label('Featured')->numeric(),
                        TextInput::make('weight_badges')->label('Badges')->numeric(),
                        TextInput::make('recommend_budget_padding')->label('Budget padding (0–1)')
                            ->numeric()->step(0.05)
                            ->helperText('Smart recommendations treat the client max as typical spend plus this extra. 0.25 = 25%.'),
                    ])->columns(4),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save search engine')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('search', $this->form->getState());

        Notification::make()->title('Search engine settings saved')->success()->send();
    }
}
