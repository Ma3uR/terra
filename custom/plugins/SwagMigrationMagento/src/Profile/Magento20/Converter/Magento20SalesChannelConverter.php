<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\MigrationMagento\Profile\Magento20\Converter;

use Swag\MigrationMagento\Profile\Magento\DataSelection\DataSet\SalesChannelDataSet;
use Swag\MigrationMagento\Profile\Magento2\Converter\Magento2SalesChannelConverter;
use Swag\MigrationMagento\Profile\Magento20\Magento20Profile;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class Magento20SalesChannelConverter extends Magento2SalesChannelConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Magento20Profile
            && $this->getDataSetEntity($migrationContext) === SalesChannelDataSet::getEntity();
    }
}