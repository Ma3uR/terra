<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;

interface Exporter
{
    /**
     * Exports a chunk of CSV data from table pickware_erp_import_export_element.
     *
     * The chunk size can be chosen by the implementation of the method.
     *
     * Notes for implementation:
     *  * You should choose a chunk size so that the process time takes about 1 second as that allows the Message Queue
     *    to export multiple chunks per iteration even in case of system slowdown. A notably smaller chunk size would
     *    instead cause many database reads and writes per second which shouldn't happen in a production system if it
     *    can be avoided.
     *
     * @param string $exportId
     * @param int $nextElementToWrite start of the next chunk
     * @param Context $context
     * @return int|null the index of the next unprocessed element, null if there are no elements left to export
     */
    public function exportChunk(string $exportId, int $nextElementToWrite, Context $context): ?int;

    /**
     * Returns the EntityDefinition the Exporter is based on.
     *
     * @return EntityDefinition The EntityDefinition the Exporter is based on
     */
    public function getEntityDefinition(): EntityDefinition;
}
