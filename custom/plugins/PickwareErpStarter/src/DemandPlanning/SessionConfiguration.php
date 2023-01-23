<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning;

use DateInterval;
use DateTime;
use DateTimeInterface;
use JsonSerializable;

class SessionConfiguration implements JsonSerializable
{
    private const SALES_REFERENCE_INTERVAL_SELECTION_OPTION_CUSTOM = 'custom';
    private const SALES_REFERENCE_INTERVAL_SELECTION_OPTIONS = [
        self::SALES_REFERENCE_INTERVAL_SELECTION_OPTION_CUSTOM => null,
        '1week' => 7,
        '2weeks' => 2 * 7,
        '1month' => 30,
        '2months' => 2 * 30,
        '3months' => 3 * 30,
        '6months' => 6 * 30,
        '12months' => 12 * 30,
    ];

    /**
     * @var bool
     */
    private $showOnlyStockAtOrBelowReorderPoint;

    /**
     * @var bool
     */
    private $considerOpenOrdersInPurchaseSuggestion;

    /**
     * @var int
     */
    private $salesPredictionDays;

    /**
     * @var String
     */
    private $salesReferenceIntervalSelectionKey;

    /**
     * @var DateTime
     */
    private $salesReferenceIntervalFromDate;

    /**
     * @var DateTime
     */
    private $salesReferenceIntervalToDate;

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $json = get_object_vars($this);
        $json['salesReferenceIntervalFromDate'] = $this->salesReferenceIntervalFromDate->format(DateTimeInterface::ATOM);
        $json['salesReferenceIntervalToDate'] = $this->salesReferenceIntervalToDate->format(DateTimeInterface::ATOM);

        return $json;
    }

    public static function fromArray(array $array): self
    {
        $self = new self();

        foreach (array_keys(get_object_vars($self)) as $key) {
            if (!isset($array[$key])) {
                throw new \InvalidArgumentException(sprintf('Property "%s" is missing in the configuration', $key));
            }
        }

        $self->showOnlyStockAtOrBelowReorderPoint = (bool) $array['showOnlyStockAtOrBelowReorderPoint'];
        $self->considerOpenOrdersInPurchaseSuggestion = (bool) $array['considerOpenOrdersInPurchaseSuggestion'];
        $self->salesPredictionDays = (int) $array['salesPredictionDays'];
        $self->salesReferenceIntervalSelectionKey = (string) $array['salesReferenceIntervalSelectionKey'];
        $self->salesReferenceIntervalFromDate = new DateTime($array['salesReferenceIntervalFromDate']);
        $self->salesReferenceIntervalToDate = new DateTime($array['salesReferenceIntervalToDate']);

        // If the sales reference interval selection is _not_ the 'custom' interval selection, recalculate the interval
        // dates based on the reference interval selection anew (e.g. the last 7 days).
        if ($self->salesReferenceIntervalSelectionKey !== self::SALES_REFERENCE_INTERVAL_SELECTION_OPTION_CUSTOM) {
            $self->salesReferenceIntervalFromDate = self::getFromDateFromSalesIntervalSelectionKey(
                $self->salesReferenceIntervalSelectionKey
            );
            $self->salesReferenceIntervalToDate = new DateTime();
        }

        return $self;
    }

    public static function createDefault(): self
    {
        return self::fromArray([
            'showOnlyStockAtOrBelowReorderPoint' => false,
            'considerOpenOrdersInPurchaseSuggestion' => true,
            'salesPredictionDays' => 30,
            'salesReferenceIntervalSelectionKey' => '1month',
            'salesReferenceIntervalFromDate' => '',
            'salesReferenceIntervalToDate' => '',
        ]);
    }

    public function getShowOnlyStockAtOrBelowReorderPoint(): bool
    {
        return $this->showOnlyStockAtOrBelowReorderPoint;
    }

    public function setShowOnlyStockAtOrBelowReorderPoint(bool $showOnlyStockAtOrBelowReorderPoint): void
    {
        $this->showOnlyStockAtOrBelowReorderPoint = $showOnlyStockAtOrBelowReorderPoint;
    }

    public function getConsiderOpenOrdersInPurchaseSuggestion(): bool
    {
        return $this->considerOpenOrdersInPurchaseSuggestion;
    }

    public function setConsiderOpenOrdersInPurchaseSuggestion(bool $considerOpenOrdersInPurchaseSuggestion): void
    {
        $this->considerOpenOrdersInPurchaseSuggestion = $considerOpenOrdersInPurchaseSuggestion;
    }

    public function getSalesPredictionDays(): int
    {
        return $this->salesPredictionDays;
    }

    public function setSalesPredictionDays(int $salesPredictionDays): void
    {
        $this->salesPredictionDays = $salesPredictionDays;
    }

    public function getSalesReferenceIntervalSelectionKey(): string
    {
        return $this->salesReferenceIntervalSelectionKey;
    }

    public function setSalesReferenceIntervalSelectionKey(string $salesReferenceIntervalSelectionKey): void
    {
        $this->salesReferenceIntervalSelectionKey = $salesReferenceIntervalSelectionKey;
    }

    public function getSalesReferenceIntervalFromDate(): DateTime
    {
        return $this->salesReferenceIntervalFromDate;
    }

    public function setSalesReferenceIntervalFromDate(string $salesReferenceIntervalFromDate): void
    {
        $this->salesReferenceIntervalFromDate = $salesReferenceIntervalFromDate;
    }

    public function getSalesReferenceIntervalToDate(): DateTime
    {
        return $this->salesReferenceIntervalToDate;
    }

    public function setSalesReferenceIntervalToDate(string $salesReferenceIntervalToDate): void
    {
        $this->salesReferenceIntervalToDate = $salesReferenceIntervalToDate;
    }

    public function getReferenceSalesToPredictionFactor(): float
    {
        // Since the date interval is considered to include both start and end date, we need to add one day to the
        // number of days between the dates. (E.g. the interval 1 to 10 includes 10 days, but the difference is 9)
        $referenceIntervalNumberOfDays = (int) $this->getSalesReferenceIntervalToDate()
                ->diff($this->getSalesReferenceIntervalFromDate(), true)
                ->format('%a') + 1;

        return $this->getSalesPredictionDays() / $referenceIntervalNumberOfDays;
    }

    private static function getFromDateFromSalesIntervalSelectionKey(string $key): DateTime
    {
        if (!array_key_exists($key, self::SALES_REFERENCE_INTERVAL_SELECTION_OPTIONS)) {
            throw new \InvalidArgumentException(sprintf('Unknown sales reference interval selection key "%s"', $key));
        }

        // Since the date interval is considered to include both start and end date, we need to subtract one day from
        // the number of days in the interval. (E.g. the interval 1 to 10 includes 10 days, but the difference is 9)
        $fromDate = new DateTime();
        $salesReferenceIntervalInDays = self::SALES_REFERENCE_INTERVAL_SELECTION_OPTIONS[$key];
        $fromDate->sub(new DateInterval(
            sprintf('P%sD', ($salesReferenceIntervalInDays - 1))
        ));

        return $fromDate;
    }
}
