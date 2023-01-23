<?php declare(strict_types=1);

/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Service;

use Shopware\Core\Framework\Context;
use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Core\Content\BilobaIntTranslation\LogEntity;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;

class TranslationLogService implements TranslationLogServiceInterface {

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var EntityRepositoryInterface
     */
    private $logRepository;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EntityRepositoryInterface $logRepository)
    {
        $this->container = $container;
        $this->log = $logger;
        $this->logRepository = $logRepository;
    }

    public function write(TranslationContext $context, string $type, ?string $status=null): void
    {
        $logFields = [];
        $logFields['initiator'] = $context->getInitiator();
        $logFields['entityType'] = $context->getEntityType();
        $logFields['entityId'] =  $context->getEntityId();
        $logFields['type'] = $type;
        $logFields['status'] = $status;
        $logFields['context'] = $context->jsonSerialize();

        $this->logRepository->create([
            $logFields
        ], Context::createDefaultContext());
    }

    public function getNumberOfTranslations(TranslationContext $context): int
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('initiator', $context->getInitiator()),
            new EqualsFilter('entityType', $context->getEntityType()),
            new EqualsFilter('type', TranslationLogServiceInterface::TYPE_TRANSLATE)
        );

        $results = $this->logRepository->search($criteria, $context->getShopwareContext());

        return $results->getTotal();
    }
}