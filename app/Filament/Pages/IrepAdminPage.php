<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class IrepAdminPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Real Estate';

    protected static ?string $title = 'Visual Editor';

    protected static ?string $navigationLabel = 'Visual Editor';

    protected static ?int $navigationSort = 2;

    public function getView(): string
    {
        return 'filament.pages.irep-admin-page';
    }

    public function getViewData(): array
    {
        return [
            'irePlugin' => [
                'ajax_url'            => route('irep.handle'),
                'nonce'               => csrf_token(),
                'contact_admin_url'   => '/admin',
                'plugin_url'          => url('/admin/irep-admin-page'),
                'plugin_assets_path'  => '/storage/irep/',
                'is_premium'          => true,
                'is_gold'             => true,
                'translations'        => [],
                'price_history_addon' => false,
                'building_360_addon'  => false,
            ],
        ];
    }
}
