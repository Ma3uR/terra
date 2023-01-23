<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\OrderItem;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\OrderItems;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;

/**
 * Class CampaignOrderSync
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
class CampaignOrderSync extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 10;

    /**
     * Associative array [item_id => mailing_id].
     *
     * @var array
     */
    private $orderItemsIdMailingIdMap;

    /**
     * CampaignOrderSync constructor.
     *
     * @param array $orderItemsIdMailingIdMap Associative array where Order item ID is key and mailing id is value.
     */
    public function __construct(array $orderItemsIdMailingIdMap)
    {
        $this->orderItemsIdMailingIdMap = $orderItemsIdMailingIdMap;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static($array['orderItemsIdMailingIdMap']);
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->orderItemsIdMailingIdMap);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);

        if (isset($unserialized['orderItemsIdMailingIdMap'])) {
            $this->orderItemsIdMailingIdMap = $unserialized['orderItemsIdMailingIdMap'];
        } else {
            $this->orderItemsIdMailingIdMap = $unserialized;
        }
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('orderItemsIdMailingIdMap' => $this->orderItemsIdMailingIdMap);
    }

    /**
     * Runs task execution.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_PROGRESS_PERCENT);
        /** @var OrderItems $orderItemService */
        $orderItemService = ServiceRegister::getService(OrderItems::CLASS_NAME);
        $orderItems = $orderItemService->getOrderItems(array_keys($this->orderItemsIdMailingIdMap));

        if (!empty($orderItems)) {
            $this->reportAlive();
            $this->setMailingIds($orderItems);
            $this->reportProgress(50);
            $this->updateRecipientWithOrderItemsInformation($orderItems);
        }

        $this->reportProgress(100);
    }

    /**
     * Iterate through passed OrderItems and sets mailing ID.
     *
     * @param OrderItem[]|null $orderItems array of Order item object.
     */
    private function setMailingIds($orderItems)
    {
        foreach ($orderItems as $orderItem) {
            $mailingId = $this->orderItemsIdMailingIdMap[$orderItem->getOrderItemId()];

            if ($mailingId !== null) {
                $orderItem->setMailingId($mailingId);
            }
        }
    }

    /**
     * Update recipient with with purchase information.
     *
     * @param OrderItem[] $orderItems array of OrderItem objects fetched from OrderItemsService
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function updateRecipientWithOrderItemsInformation($orderItems)
    {
        $firstItem = $this->getFirstOrderItem($orderItems);
        $recipientEmail = $firstItem ? $firstItem->getRecipientEmail() : null;
        $lastOrderDate = $firstItem ? $firstItem->getStamp() : null;

        $this->getProxy()->uploadOrderItems($recipientEmail, $orderItems, $lastOrderDate);
    }

    /**
     * Returns first item from array
     *
     * @param OrderItem[] $orderItems array of OrderItem objects fetched from OrderItemsService
     *
     * @return OrderItem|null
     */
    private function getFirstOrderItem($orderItems)
    {
        foreach ($orderItems as $orderItem) {
            return $orderItem;
        }

        return null;
    }
}
