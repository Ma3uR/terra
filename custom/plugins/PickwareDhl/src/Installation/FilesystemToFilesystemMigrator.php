<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Installation;

use League\Flysystem\FilesystemInterface;
use RuntimeException;

class FilesystemToFilesystemMigrator
{
    /**
     * @var FilesystemInterface
     */
    private $sourceFilesystem;

    /**
     * @var FilesystemInterface
     */
    private $destinationFilesystem;

    public function __construct(FilesystemInterface $sourceFilesystem, FilesystemInterface $destinationFilesystem)
    {
        $this->sourceFilesystem = $sourceFilesystem;
        $this->destinationFilesystem = $destinationFilesystem;
    }

    public function moveDirectory(string $dir): void
    {
        if (!$this->destinationFilesystem->has($dir)) {
            $this->destinationFilesystem->createDir($dir);
        }
        if (!$this->sourceFilesystem->has($dir)) {
            return;
        }

        $dirItems = $this->sourceFilesystem->listContents($dir);
        foreach ($dirItems as $dirItem) {
            if ($this->destinationFilesystem->has($dirItem['path'])) {
                // Assumption: The file is broken from a previously failed copy process
                $this->destinationFilesystem->delete($dirItem['path']);
            }

            $readSteam = $this->sourceFilesystem->readStream($dirItem['path']);
            $copySuccessful = $this->destinationFilesystem->writeStream($dirItem['path'], $readSteam);
            if (!$copySuccessful) {
                throw new RuntimeException(
                    'An error has occurred while copying files from one filesystem to another.'
                );
            }
            $this->sourceFilesystem->delete($dirItem['path']);
        }

        $this->sourceFilesystem->deleteDir($dir);
    }
}
