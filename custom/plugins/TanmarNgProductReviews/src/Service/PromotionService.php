<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Service;

use Tanmar\ProductReviews\Service\ConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeEntity;
use Shopware\Core\Checkout\Promotion\Util\PromotionCodeService;

class PromotionService {

    /**
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $promotionRepository;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $promotionIndividualCodeRepository;

    /**
     *
     * @var PromotionCodeService
     */
    protected $codeService;

    public function __construct(ConfigService $configService, EntityRepositoryInterface $promotionRepository, EntityRepositoryInterface $promotionIndividualCodeRepository, PromotionCodeService $codeService) {
        $this->configService = $configService;
        $this->promotionRepository = $promotionRepository;
        $this->promotionIndividualCodeRepository = $promotionIndividualCodeRepository;
        $this->codeService = $codeService;
    }

    /**
     * 
     * @param string $promotionId
     * @param Context $context
     * @return string
     */
    public function getPromotionCode(string $promotionId, Context $context): string {
        try {
            if ($promotionId != '') {
                $promotionCriteria = new Criteria();
                $promotionCriteria->addAssociation('individualCodes');
                $promotionCriteria->addFilter(new EqualsFilter('active', 1));
                $promotionCriteria->addFilter(new EqualsFilter('useIndividualCodes', 1));
                $promotionCriteria->addFilter(new EqualsFilter('id', $promotionId));

                $promotions = $this->promotionRepository->search($promotionCriteria, $context);
                $promotion = $promotions->first();

                if (is_null($promotion)) {
                    return '';
                } else {
                    $code = $this->createNewCode($promotion, $context);
                    if (is_null($code)) {
                        return '';
                    } else {
                        return $code->getCode();
                    }
                }
            } else {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 
     * @param PromotionEntity $promotion
     * @param Context $context
     * @return PromotionIndividualCodeEntity|null
     */
    protected function createNewCode(PromotionEntity $promotion, Context $context): ?PromotionIndividualCodeEntity {
        $this->codeService->addIndividualCodes($promotion->getId(), 1, $context);
        return $this->getLatestCode($promotion, $context);
    }

    /**
     * 
     * @param PromotionEntity $promotion
     * @param Context $context
     * @return PromotionIndividualCodeEntity|null
     */
    protected function getLatestCode(PromotionEntity $promotion, Context $context): ?PromotionIndividualCodeEntity {
        try {
            $codeCriteria = new Criteria();
            $codeCriteria->addFilter(new EqualsFilter('promotionId', $promotion->getId()));
            $codeCriteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
            $newCodes = $this->promotionIndividualCodeRepository->search($codeCriteria, $context);
            $individualCode = $newCodes->first();
            return $individualCode;
        } catch (\Exception $e) {
            return null;
        }
    }

}
