<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\InstallationLibrary\MailTemplate;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class MailTemplateTranslation
{
    /**
     * @var string
     */
    private $localeCode;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $sender;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $contentHtml;

    /**
     * @var string
     */
    private $contentPlain;

    public static function createFromYamlFile(string $filename): self
    {
        try {
            $mailTemplateTranslation = Yaml::parseFile($filename);
        } catch (ParseException $e) {
            // throw runtime exception because an invalid yaml is ALWAYS a programming error here.
            throw new \RuntimeException(sprintf(
                'Yaml file %s could not be parsed: %s',
                $filename,
                $e->getMessage()
            ), 0, $e);
        }
        $mailTemplateTranslation['contentHtml'] = file_get_contents(
            dirname($filename) . '/' . $mailTemplateTranslation['contentHtml']['fromFile']
        );
        $mailTemplateTranslation['contentPlain'] = file_get_contents(
            dirname($filename) . '/' . $mailTemplateTranslation['contentPlain']['fromFile']
        );

        $self = new self();
        $self->localeCode = $mailTemplateTranslation['localeCode'];
        $self->subject = $mailTemplateTranslation['subject'];
        $self->sender = $mailTemplateTranslation['sender'];
        $self->description = $mailTemplateTranslation['description'];
        $self->contentHtml = $mailTemplateTranslation['contentHtml'];
        $self->contentPlain = $mailTemplateTranslation['contentPlain'];

        return $self;
    }

    private function __construct()
    {
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContentHtml(): string
    {
        return $this->contentHtml;
    }

    public function getContentPlain(): string
    {
        return $this->contentPlain;
    }
}
