<?php declare(strict_types=1);

namespace Biloba\IntlTranslation;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class BilobaIntlTranslation extends Plugin
{
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        $connection->executeQuery('DROP TABLE IF EXISTS `biloba_intl_translation_config`');
        $connection->executeQuery('DROP TABLE IF EXISTS `biloba_intl_translation_log`');
    }
}