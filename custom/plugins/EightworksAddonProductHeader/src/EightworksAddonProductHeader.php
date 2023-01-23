<?php declare(strict_types=1);

namespace Eightworks\EightworksAddonProductHeader;

/**
 * \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 *  ______ ________ _______ ______ __  __ _______
 * |  __  |  |  |  |       |   __ \  |/  |     __|
 * |  __  |  |  |  |   -   |      <     <|__     |
 * |______|________|_______|___|__|__|\__|_______|
 *
 *  ####+++---   C  O  N  T  A  C  T   –--++++####
 *
 *  Internetagentur 8works
 *  WEB: 8works.de
 *  MAIL: info@8works.de
 *
 * \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 **/

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Eightworks\EightworksAddonProductHeader\Service\CustomFieldService;

class EightworksAddonProductHeader extends Plugin
{

    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Install plugin
     *
     * @param InstallContext $context
     * @return void
     */
    public function install(InstallContext $context) : void
    {
        parent::install($context);

        $customFieldService = new CustomFieldService(
            $this->container,
            $this->container->get('custom_field_set.repository')
        );

        // Add custom fields
        $customFieldService->addCustomFields($context->getContext());
    }

    /**
     * Uninstall plugin
     *
     * @param UninstallContext $context
     * @return void
     */
    public function uninstall(UninstallContext $context) : void
    {
        parent::uninstall($context);

        // Add custom fields
        $customFieldService = new CustomFieldService(
            $this->container,
            $this->container->get('custom_field_set.repository')
        );

        // Delete custom fields
        $customFieldService->deleteCustomFields($context->getContext());
    }

    /**
     * Activate plugin
     *
     * @param ActivateContext $context
     * @return void
     */
    public function activate(ActivateContext $context) : void
    {
        parent::activate($context);
    }

    /**
     * Deactivate plugin
     *
     * @param DeactivateContext $context
     * @return void
     */
    public function deactivate(DeactivateContext $context) : void
    {
        parent::deactivate($context);
    }
}

