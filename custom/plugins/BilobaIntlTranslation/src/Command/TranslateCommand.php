<?php declare(strict_types = 1);

namespace Biloba\IntlTranslation\Command;

use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Biloba\IntlTranslation\Service\TranslatorService;
use Symfony\Component\Console\Helper\Table;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Biloba\IntlTranslation\Struct\TranslationContext;

class TranslateCommand extends BaseCommand
{
    protected static $defaultName = 'biloba:intl_translation:translate';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->writeln('Do something...');

        $apis = $this->translatorService->getTranslationApis();

        $tableHeader = ['Sprache'];
        foreach($apis as $api) {
        	$tableHeader[] = $api->getLabel();
        }

        $systemLanguages = $this->getSystemLanguages();

        $supportedLanguages = [];
        foreach($systemLanguages as $languageEntity) {
			$row = [
        		$languageEntity->getName()
        	];

        	foreach($apis as $api) {
        		list($language, $area) = explode('-', $languageEntity->getLocale()->getCode());

        		$row[] = in_array($language, $api->getSupportedLanguages()) ? 'x' : '';
        	}

        	$supportedLanguages[] = $row;
        }

        $table = new Table($output);
        $table
            ->setHeaders($tableHeader)
            ->setRows($supportedLanguages);
        $table->render();
    }

    private function getSystemLanguages() {
        /** @todo Load real default language! */

        /** @var EntityRepositoryInterface $productRepository */
        $repository = $this->container->get('language.repository');

        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        
        /** @var EntitySearchResult $products */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());

        return $searchResult;
    }
}