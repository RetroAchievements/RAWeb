<?php

namespace App\Filament;

use Filament\Forms\Components\DateTimePicker;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Infolist;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;

class FilamentServiceProvider extends PanelProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions(config('filament.default_page_options'));
        });

        Table::$defaultDateTimeDisplayFormat = 'Y-m-d H:i:s';
        Infolist::$defaultDateTimeDisplayFormat = 'Y-m-d H:i:s';
        DateTimePicker::$defaultDateDisplayFormat = 'Y-m-d';
        DateTimePicker::$defaultDateTimeDisplayFormat = 'Y-m-d H:i';
        DateTimePicker::$defaultDateTimeWithSecondsDisplayFormat = 'Y-m-d H:i:s';
        DateTimePicker::$defaultTimeDisplayFormat = 'H:i';
        DateTimePicker::$defaultTimeWithSecondsDisplayFormat = 'H:i:s';
    }

    public function panel(Panel $panel): Panel
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
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Sky,
                'primary' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->maxContentWidth(MaxWidth::ScreenTwoExtraLarge)
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
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                Widgets\SiteInfoWidget::class,
            ])
            ->middleware([
                'web',
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
