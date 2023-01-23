<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class PluginTest extends TestCase {

    public function testClassesAreInstantiable(): void {
        $namespace = str_replace('\Test', '', __NAMESPACE__);

        foreach ($this->getPluginClasses() as $class) {
            $classRelativePath = str_replace(['.php', '/'], ['', '\\'], $class->getRelativePathname());

            $this->getMockBuilder($namespace . '\\' . $classRelativePath)
                ->disableOriginalConstructor()
                ->getMock();
            $this->assertTrue(true);
        }
    }

    private function getPluginClasses(): Finder {
        $finder = new Finder();
        $finder->in(realpath(__DIR__ . '/../'));
        $finder->exclude('Test');
        return $finder->files()->name('*.php');
    }

}
