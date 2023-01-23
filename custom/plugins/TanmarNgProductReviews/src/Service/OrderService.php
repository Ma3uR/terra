<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Service;

use Tanmar\ProductReviews\Service\ConfigService;
use Tanmar\ProductReviews\Components\Installer\OrderCustomFieldInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderService {

    /**
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(ConfigService $configService, EntityRepositoryInterface $orderRepository) {
        $this->configService = $configService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 
     * also false in exception or empty $promotionCode
     * 
     * @param Context $context
     * @param string $promotionCode
     * @return bool
     */
    public function otherOrderHasPromotionCode(Context $context, string $promotionCode): bool {
        if ($promotionCode != '') {
            try {
                $orderCriteria = new Criteria();
                $orderCriteria->addFilter(new EqualsFilter('customFields.' . OrderCustomFieldInstaller::CUSTOM_FIELD_PROMOTION_CODE, $promotionCode));

                $orders = $this->orderRepository->search($orderCriteria, $context);
                $orderEntity = $orders->first();
                if (!is_null($orderEntity)) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * @param OrderEntity $orderEntity
     * @return bool
     */
    public function hasPromotionCode(OrderEntity $orderEntity): bool {
        try {
            $customFields = $orderEntity->getCustomFields();
            foreach ($customFields as $key => $customField) {
                if (($key == OrderCustomFieldInstaller::CUSTOM_FIELD_PROMOTION_CODE) && ($customField != '')) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * 
     * @param OrderEntity $orderEntity
     * @param Context $context
     * @param string $promotionCode
     * @return bool
     */
    public function updatePromotionCode(OrderEntity $orderEntity, Context $context, string $promotionCode): bool {
        if ($promotionCode != '') {
            try {
                $newOrderCustomFields = [
                    OrderCustomFieldInstaller::CUSTOM_FIELD_PROMOTION_CODE => $promotionCode
                ];

                $orderCustomFields = is_array($orderEntity->getCustomFields()) ? $orderEntity->getCustomFields() : array();

                $customFields = array_merge($orderCustomFields, $newOrderCustomFields);

                return $this->updateOrder($orderEntity->getId(), $customFields, $context);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * @param OrderEntity $orderEntity
     * @param Context $context
     * @param string $optin
     * @return bool
     */
    public function initializeOrderFields(OrderEntity $orderEntity, Context $context, string $optin = 'not asked'): bool {
        if ($optin != '') {
            try {
                $newOrderCustomFields = [
                    OrderCustomFieldInstaller::CUSTOM_FIELD_OPTIN => $optin,
                    OrderCustomFieldInstaller::CUSTOM_FIELD_SENT => null,
                    OrderCustomFieldInstaller::CUSTOM_FIELD_STATUS => 'open'
                ];

                $orderCustomFields = is_array($orderEntity->getCustomFields()) ? $orderEntity->getCustomFields() : array();

                $customFields = array_merge($orderCustomFields, $newOrderCustomFields);

                return $this->updateOrder($orderEntity->getId(), $customFields, $context);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function updateOrder(string $orderId, array $customFields, Context $context): bool {
        try {
            $order = [
                'id' => $orderId,
                'customFields' => $customFields
            ];

            $this->orderRepository->update([$order], $context);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

}
