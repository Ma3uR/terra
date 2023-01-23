<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use \Tanmar\ProductReviews\Components\Installer\MailTemplateInstaller;
use \Tanmar\ProductReviews\Components\Installer\OrderCustomFieldInstaller;

class TanmarNgProductReviews extends Plugin {

    public function install(InstallContext $installContext): void {
        parent::install($installContext);
        $this->installMailTemplate($installContext->getContext());
        $this->installCustomFields($installContext->getContext());
    }

    public function update(UpdateContext $context): void {
        parent::update($context);
        // can be removed, when all customers are >= 1.2.0
        if ((version_compare($context->getCurrentPluginVersion(), '1.2.0') < 0) && (version_compare($context->getUpdatePluginVersion(), '1.2.0') >= 0)) {
            $this->deleteCustomFields($context->getContext());
            $this->installCustomFields($context->getContext());
        }
    }

    public function activate(ActivateContext $context): void {
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void {
        parent::deactivate($context);
    }

    public function uninstall(UninstallContext $context): void {
        parent::uninstall($context);
        if (!$context->keepUserData()) {
            $this->deleteMailTemplate($context->getContext());
            $this->deleteCustomFields($context->getContext());
        }
    }

    /**
     *
     *
     * @param Context $context
     * @return void
     */
    private function installCustomFields(Context $context): void {
        $installer = new OrderCustomFieldInstaller($context, $this->container);
        $installer->install();
    }

    /**
     * deletes custom fields
     *
     * @param Context $context
     * @return void
     */
    private function deleteCustomFields(Context $context): void {
        $installer = new OrderCustomFieldInstaller($context, $this->container);
        $installer->uninstall();
    }

    /**
     * creates all mail templates of plugin on install
     *
     * @param Context $context
     * @return void
     */
    private function installMailTemplate(Context $context): void {
        $installer = new MailTemplateInstaller($context, $this->container);
        $installer->install();
    }

    /**
     * deletes all mail templates and template types of plugin on uninstall
     *
     * @param Context $context
     * @return void
     */
    private function deleteMailTemplate(Context $context): void {
        $installer = new MailTemplateInstaller($context, $this->container);
        $installer->uninstall();
    }

    /**
     *
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void {
        parent::build($container);
    }

}
