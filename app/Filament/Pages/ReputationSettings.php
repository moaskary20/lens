<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Feature;
use App\Support\Reputation;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
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

class ReputationSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Reputation rules';

    protected static ?string $title = 'When ratings, badges and featured placement apply';

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings')
            && (Feature::enabled('reviews') || Feature::enabled('badges'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(Reputation::settings());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Star ratings, session metrics & incentive badges')
                    ->description('Clients rate 1–5 stars after they approve a session. Those scores rank search. High completion + positive reviews award Top Rated / Popular and featured placement.')
                    ->schema([
                        Toggle::make('reviews_only_after_approval')->label('Allow reviews only after session approval'),
                        Toggle::make('ranking_uses_ratings')->label('Ratings affect search ranking'),
                        Toggle::make('auto_award_badges')->label('Auto-award Top Rated / Popular badges'),
                        Toggle::make('auto_feature_top_vendors')->label('Feature vendors who earn those badges'),
                        TextInput::make('top_rated_min')->label('Top Rated minimum stars')->numeric()->step(0.1),
                        TextInput::make('top_rated_min_reviews')->label('Top Rated minimum reviews')->numeric(),
                        TextInput::make('popular_min_completed')->label('Popular minimum completed sessions')->numeric(),
                        TextInput::make('popular_min_completion_rate')->label('Popular minimum completion rate (0–1)')->numeric()->step(0.05)
                            ->helperText('Example: 0.8 means the vendor must complete at least 80% of booked sessions.'),
                        TextInput::make('popular_min_rating')->label('Popular minimum average stars')->numeric()->step(0.1),
                    ])->columns(2),
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
                        Action::make('save')->label('Save reputation rules')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('reputation', $this->form->getState());

        Notification::make()->title('Reputation rules saved')->success()->send();
    }
}
