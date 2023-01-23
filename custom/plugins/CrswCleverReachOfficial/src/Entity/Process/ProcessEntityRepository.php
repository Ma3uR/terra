<?php

namespace Crsw\CleverReachOfficial\Entity\Process;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProcessEntityRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Process
 */
class ProcessEntityRepository
{

    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * ProcessEntityRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Creates/Updates process
     *
     * @param string $guid
     * @param string $serializedRunner
     * @param Context $context
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function saveGuidAndRunner(string $guid, string $serializedRunner, Context $context): void
    {
        $process = $this->getProcessByGuid($guid, $context);
        if (!$process) {
            $this->baseRepository->create(
                [
                    ['guid' => $guid, 'runner' => $serializedRunner]
                ],
                $context
            );
        } else {
            $this->baseRepository->update([
                ['id' => $process->getId(), 'guid' => $guid, 'runner' => $serializedRunner],
            ],
                $context
            );
        }
    }

    /**
     * Deletes process by its guid
     *
     * @param string $guid
     * @param Context $context
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function deleteByGuid(string $guid, Context $context): void
    {
        $process = $this->getProcessByGuid($guid, $context);
        if ($process) {
            $this->baseRepository->delete([['id' => $process->getId()]], $context);
        }
    }

    /**
     * Returns process by guid
     *
     * @param string $guid
     * @param Context $context
     *
     * @return ProcessEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getProcessByGuid(string $guid, Context $context): ?ProcessEntity
    {
        $results = $this->baseRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('guid', $guid)),
            $context
        );

        return $results->getEntities()->first();
    }
}
