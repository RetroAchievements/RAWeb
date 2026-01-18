<?php

namespace App\Filament;

use App\Filament\GlobalSearch\RelevanceBasedGlobalSearchProvider;
use App\Filament\Widgets\SiteInfoWidget;
use App\Models\AchievementSetClaim;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

class FilamentServiceProvider extends \Filament\PanelProvider
{
    public function boot(): void
    {
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->paginationPageOptions(config('filament.default_page_options'));
        });

        \Filament\Tables\Table::configureUsing(fn (\Filament\Tables\Table $table) => $table->defaultDateTimeDisplayFormat('Y-m-d H:i:s'));
        \Filament\Schemas\Schema::configureUsing(fn (\Filament\Schemas\Schema $schema) => $schema->defaultDateTimeDisplayFormat('Y-m-d H:i:s'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn (\Filament\Forms\Components\DateTimePicker $dateTimePicker) => $dateTimePicker->defaultDateDisplayFormat('Y-m-d'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn (\Filament\Forms\Components\DateTimePicker $dateTimePicker) => $dateTimePicker->defaultDateTimeDisplayFormat('Y-m-d H:i'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn (\Filament\Forms\Components\DateTimePicker $dateTimePicker) => $dateTimePicker->defaultDateTimeWithSecondsDisplayFormat('Y-m-d H:i:s'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn (\Filament\Forms\Components\DateTimePicker $dateTimePicker) => $dateTimePicker->defaultTimeDisplayFormat('H:i'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn (\Filament\Forms\Components\DateTimePicker $dateTimePicker) => $dateTimePicker->defaultTimeWithSecondsDisplayFormat('H:i:s'));
    }

    public function panel(\Filament\Panel $panel): \Filament\Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('manage')
            // ->login() login is handled by application
            ->brandLogo(asset('assets/images/ra-icon.webp'))
            ->favicon(
                !app()->environment('production')
                    ? asset('assets/images/favicon-gray.webp')
                    : asset('assets/images/favicon.webp')
            )
            ->homeUrl(url('/'))
            ->colors([
                'danger' => \Filament\Support\Colors\Color::Red,
                'gray' => \Filament\Support\Colors\Color::Gray,
                'info' => \Filament\Support\Colors\Color::Sky,
                'primary' => \Filament\Support\Colors\Color::Blue,
                'success' => \Filament\Support\Colors\Color::Green,
                'warning' => \Filament\Support\Colors\Color::Amber,
            ])
            ->globalSearch(RelevanceBasedGlobalSearchProvider::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->maxContentWidth(\Filament\Support\Enums\Width::ScreenTwoExtraLarge)
            ->navigationGroups([
                'Community',
                'Development',
                'Platform',
                'Releases',
                'Players',
                'Settings',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
                SiteInfoWidget::class,
            ])
            ->renderHook(
                'panels::head.start',
                fn () => Blade::render('<x-head-analytics />')
            )
            ->viteTheme('resources/filament/css/theme.css')
            ->middleware([
                'web',
                \Filament\Http\Middleware\DisableBladeIconComponents::class,
                \Filament\Http\Middleware\DispatchServingFilamentEvent::class,
                \App\Http\Middleware\UseViteForFilament::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ])
            ->userMenuItems([
                \Filament\Actions\Action::make('view-on-site')
                    ->label('Profile')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function () {
                        /** @var User $user */
                        $user = Auth::user();

                        return $user->canonicalUrl;
                    })
                    ->sort(1),

                \Filament\Actions\Action::make('my-claims')
                    ->label('Active Claims')
                    ->icon('heroicon-m-flag')
                    ->url(fn (): string => Resources\AchievementSetClaimResource::getUrl('index', [
                        'filters' => [
                            'my_claims' => ['isActive' => true],
                            'status' => ['values' => ['active', 'in_review']],
                        ],
                    ]))
                    ->visible(function () {
                        /** @var User $user */
                        $user = Auth::user();

                        return $user->can('manage', AchievementSetClaim::class);
                    })
                    ->sort(2),
            ]);
    }
}
