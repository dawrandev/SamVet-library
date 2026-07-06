<?php

namespace App\Enums;

/**
 * Menu item type (client navbar navigation behavior).
 */
enum MenuItemType: string
{
    case Dropdown = 'dropdown';
    case Page = 'page';
    case Module = 'module';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Dropdown => __('Ochiluvchi'),
            self::Page => __('Sahifa'),
            self::Module => __('Bo‘lim'),
            self::External => __('Tashqi havola'),
        };
    }
}
