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

class LanguageCommand extends BaseCommand
{
    protected static $defaultName = 'biloba:intl_translation:languages';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apis = $this->translatorService->getTranslationApis();

        $tableHeader = ['Sprache'];
        foreach($apis as $api) {
        	$tableHeader[] = $api->getLabel();
        }

        $supportedLanguages = $this->translatorService->getSupportedLanguages();

        $languageMatrix = [];
        foreach($supportedLanguages as $language) {
        	$row = [
        		($language)
        	];

        	foreach($apis as $api) {
                if(in_array($language, $api->getSupportedLanguages()) || 
                   in_array(strtolower($language), $api->getSupportedLanguages())  || 
                   in_array(strtoupper($language), $api->getSupportedLanguages())) {
        		  
                    $row[] = 'x';
                } else {
                    $row[] = '';
                }
        	}

        	$languageMatrix[] = $row;
        }

        $table = new Table($output);
        $table
            ->setHeaders($tableHeader)
            ->setRows($languageMatrix);
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