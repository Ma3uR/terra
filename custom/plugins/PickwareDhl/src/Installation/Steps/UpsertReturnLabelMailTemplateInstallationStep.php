<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Installation\Steps;

use Pickware\PickwareDhl\PickwareDhl;
use Pickware\InstallationLibrary\MailTemplate\MailTemplateTranslation;
use Pickware\InstallationLibrary\MailTemplate\MailTemplateInstaller;

class UpsertReturnLabelMailTemplateInstallationStep
{
    /**
     * @var MailTemplateInstaller
     */
    private $mailTemplateInstaller;

    public function __construct(MailTemplateInstaller $mailTemplateInstaller)
    {
        $this->mailTemplateInstaller = $mailTemplateInstaller;
    }

    public function install(): void
    {
        $mailTemplateTypeId = $this->mailTemplateInstaller->ensureMailTemplateType(
            PickwareDhl::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME_RETURN_LABEL,
            [
                'de-DE' => 'DHL Rücksendeetikett',
                'en-GB' => 'DHL return label',
            ],
            []
        );
        $mailTemplateId = $this->mailTemplateInstaller->ensureMailTemplate($mailTemplateTypeId);
        $returnLabelMailTemplateDeDe = MailTemplateTranslation::createFromYamlFile(
            __DIR__ . '/../../Dhl/Resources/mail-templates/return-label/translation-de-de.yaml'
        );
        $this->mailTemplateInstaller->ensureMailTemplateTranslation($mailTemplateId, $returnLabelMailTemplateDeDe);
        $returnLabelMailTemplateEnGb = MailTemplateTranslation::createFromYamlFile(
            __DIR__ . '/../../Dhl/Resources/mail-templates/return-label/translation-en-gb.yaml'
        );
        $this->mailTemplateInstaller->ensureMailTemplateTranslation($mailTemplateId, $returnLabelMailTemplateEnGb);
    }
}
