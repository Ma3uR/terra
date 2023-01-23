<?php declare(strict_types = 1);

namespace Biloba\IntlTranslation\Command;

use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Biloba\IntlTranslation\Service\TranslatorService;
use Symfony\Component\Console\Helper\Table;
use Psr\Container\ContainerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Service\TranslatorServiceInterface;

class BaseCommand extends Command
{
    protected static $defaultName = 'biloba:intl_translation:translate';

    /** @var ContainerInterface */
    protected $container;

    /** @var TranslatorServiceInterface */
    protected $translatorService;

    /** @var TranslationContext */
    protected $context;

    public function __construct(ContainerInterface $container, 
    							SystemConfigService $systemConfigService,
    							TranslatorServiceInterface $translatorService) {
    	parent::__construct();

        $this->container = $container;

    	$this->context = new TranslationContext(
    		$systemConfigService->get('BilobaIntlTranslation.config')
    	);
        $this->context->setInitiator('BilobaIntlTranslation');

        $this->translatorService = $translatorService;
    	$this->translatorService->setContext($this->context);
    }
}