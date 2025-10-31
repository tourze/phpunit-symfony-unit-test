<?php

namespace Tourze\PHPUnitSymfonyUnitTest\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AbstractEventTestCase::class)]
class AbstractEventTestCaseTest extends TestCase
{
    #[Test]
    public function testClassCanBeReflected(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);
        $this->assertSame('Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase', $reflection->getName());
    }

    #[Test]
    public function testIsAbstract(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);
        $this->assertTrue($reflection->isAbstract());
    }

    #[Test]
    public function testExtendsTestCase(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);
        $this->assertTrue($reflection->isSubclassOf(TestCase::class));
    }

    #[Test]
    public function testHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);

        $this->assertTrue($reflection->hasMethod('testShouldNotHaveRunTestsInSeparateProcesses'));
        $this->assertTrue($reflection->hasMethod('testExtendsEvent'));
    }

    #[Test]
    public function testAllTestMethodsAreFinal(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);
        $testMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($testMethods as $method) {
            if (str_starts_with($method->getName(), 'test') && 'testCase' !== $method->getName()) {
                $this->assertTrue($method->isFinal(), "Method {$method->getName()} should be final");
            }
        }
    }

    #[Test]
    public function testHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(AbstractEventTestCase::class);
        $this->assertSame('Tourze\PHPUnitSymfonyUnitTest', $reflection->getNamespaceName());
    }

    #[Test]
    public function testUsesCorrectImports(): void
    {
        $fileContent = file_get_contents(__DIR__ . '/../src/AbstractEventTestCase.php');
        $this->assertIsString($fileContent, 'File should be readable');

        $this->assertStringContainsString('use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;', $fileContent);
        $this->assertStringContainsString('use PHPUnit\Framework\Attributes\Test;', $fileContent);
        $this->assertStringContainsString('use PHPUnit\Framework\TestCase;', $fileContent);
        $this->assertStringContainsString('use Symfony\Contracts\EventDispatcher\Event;', $fileContent);
        $this->assertStringContainsString('use Tourze\PHPUnitBase\TestCaseHelper;', $fileContent);
    }
}
