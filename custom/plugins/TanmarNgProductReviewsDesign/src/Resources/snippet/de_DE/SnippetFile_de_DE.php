<?php declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Resources\snippet\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'reviews.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/reviews.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
    }

    public function getAuthor(): string
    {
        return 'Tanmar';
    }

    public function isBase(): bool
    {
        return true;
    }
}