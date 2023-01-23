<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DocumentBundle\Model;

use Pickware\DalBundle\Field\EnumField;
use Pickware\DalBundle\Field\JsonSerializableObjectField;
use Pickware\DalBundle\Field\NonUuidFkField;
use Pickware\DocumentBundle\PageFormat;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;

class DocumentDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'pickware_document';

    public const ENTITY_LOADED_EVENT = self::ENTITY_NAME . '.loaded';
    public const ENTITY_DELETED_EVENT = self::ENTITY_NAME . '.deleted';

    public const DEEP_LINK_CODE_LENGTH = 32;

    /**
     * @inheritDoc
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getCollectionClass(): string
    {
        return DocumentCollection::class;
    }

    /**
     * @inheritDoc
     */
    public function getEntityClass(): string
    {
        return DocumentEntity::class;
    }

    /**
     * @inheritDoc
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('deep_link_code', 'deepLinkCode', self::DEEP_LINK_CODE_LENGTH))->addFlags(new Required()),

            (new NonUuidFkField(
                'document_type_technical_name',
                'documentTypeTechnicalName',
                DocumentTypeDefinition::class,
                'technicalName'
            ))->addFlags(new Required()),
            new ManyToOneAssociationField(
                'documentType',
                'document_type_technical_name',
                DocumentTypeDefinition::class,
                'technical_name'
            ),

            (new StringField('path_in_private_file_system', 'pathInPrivateFileSystem'))->addFlags(new Required()),
            (new IntField('file_size_in_bytes', 'fileSizeInBytes'))->addFlags(new Required()),

            new StringField('file_name', 'fileName'),
            new StringField('mime_type', 'mimeType'),
            new EnumField('orientation', 'orientation', DocumentEntity::ORIENTATIONS),
            new JsonSerializableObjectField('page_format', 'pageFormat', PageFormat::class),
        ]);
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            // fileSizeInBytes was added as a mandatory field. To be backwards-compatible, the default value of -1 is
            // added
            // -1 is a special value for: "File size has not yet been determined".
            // The -1 will be replaced with the actual size of the file at the next fetch of the entity, see
            // BackwardsCompatibilitySubscriber
            // NULL is not used, because the field itself should not allow you to save a file without specifying a file
            // size.
            // The case "no file size specified" occurs only for documents that were created in an old version of the
            // bundle because it was possible to create the entity first and save the file afterwards. A File size did
            // not have to be specified when creating the entity. Furthermore, existing entities were set to the value
            // -1 migrated because the file size is not known during the migration.
            // The magic value -1 was chosen because hopefully nobody will ever get the idea to specify a file size with
            // -1 in "normal operation".
            /** @deprecated 2.0.0 Default value for fileSizeInBytes will be removed */
            'fileSizeInBytes' => -1,
            // pathInPrivateFileSystem was added as a mandatory field. To be backwards-compatible, a default value is
            // added
            /** @deprecated 2.0.0 Default value for pathInPrivateFileSystem will be removed */
            'pathInPrivateFileSystem' => 'documents/' . Uuid::randomHex(),
            // Method Random::getAlphanumericString() is not sufficient as it uses uppercase and lowercase letters for
            // the generated string, but MySQL compares case-insensitive.
            'deepLinkCode' => Random::getString(self::DEEP_LINK_CODE_LENGTH, implode(range('a', 'z')) . implode(range(0, 9))),
        ];
    }
}
