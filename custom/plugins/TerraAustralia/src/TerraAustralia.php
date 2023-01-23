<?php declare(strict_types=1);

namespace TerraAustralia;

use Shopware\Core\Framework\Plugin;
use Shopware\Storefront\Framework\ThemeInterface;

class TerraAustralia extends Plugin implements ThemeInterface
{
    public function getThemeConfigPath(): string
    {
        return 'theme.json';
    }
    
    public function getServicesFilePath(): string
    {
        return 'Resources/config/services.xml';
    }
}
