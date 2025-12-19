<?php

namespace Tourze\PHPUnitSymfonyUnitTest\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AbstractDependencyInjectionExtensionTestCase::class)]
class AbstractDependencyInjectionExtensionTestCaseTest extends TestCase
{
    #[Test]
    public function testClassCanBeReflected(): void
    {
        $reflection = new \ReflectionClass(AbstractDependencyInjectionExtensionTestCase::class);
        $this->assertSame('Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase', $reflection->getName());
    }

    #[Test]
    public function testIsAbstract(): void
    {
        $reflection = new \ReflectionClass(AbstractDependencyInjectionExtensionTestCase::class);
        $this->assertTrue($reflection->isAbstract());
    }

    #[Test]
    public function testExtendsTestCase(): void
    {
        $reflection = new \ReflectionClass(AbstractDependencyInjectionExtensionTestCase::class);
        $this->assertTrue($reflection->isSubclassOf(TestCase::class));
    }

    #[Test]
    public function testHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(AbstractDependencyInjectionExtensionTestCase::class);

        $this->assertTrue($reflection->hasMethod('testShouldNotHaveRunTestsInSeparateProcesses'));
        $this->assertTrue($reflection->hasMethod('testTestCaseMustBeFinal'));
        $this->assertTrue($reflection->hasMethod('testExtendsCorrectBaseClass'));
        $this->assertTrue($reflection->hasMethod('testLoadShouldRegisterServices'));
        $this->assertTrue($reflection->hasMethod('testServiceInjectedLoggerMustUseWithMonologChannelAttribute'));
        $this->assertTrue($reflection->hasMethod('noRegisteredServicesAreBundle'));
        $this->assertTrue($reflection->hasMethod('noRegisteredServicesAreEntity'));
        $this->assertTrue($reflection->hasMethod('provideServiceDirectories'));
    }

    #[Test]
    public function testProvideServiceDirectoriesReturnsExpectedDirectories(): void
    {
        $mockClass = new /**
         * @internal
         */
        #[CoversClass(AbstractDependencyInjectionExtensionTestCase::class)] class('test') extends AbstractDependencyInjectionExtensionTestCase {
            /** @return iterable<string> */
            public function getServiceDirectories(): iterable
            {
                return $this->provideServiceDirectories();
            }
        };

        $directories = iterator_to_array($mockClass->getServiceDirectories());

        $expectedDirectories = [
            'Controller',
            'Command',
            'Service',
            'Repository',
            'EventSubscriber',
            'MessageHandler',
            'Procedure',
            'Twig',
        ];

        $this->assertSame($expectedDirectories, $directories);
    }

    #[Test]
    public function testAllTestMethodsAreFinal(): void
    {
        $reflection = new \ReflectionClass(AbstractDependencyInjectionExtensionTestCase::class);
        $testMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($testMethods as $method) {
            if (str_starts_with($method->getName(), 'test') && 'testCase' !== $method->getName()) {
                $this->assertTrue($method->isFinal(), "Method {$method->getName()} should be final");
            }
        }
    }
}
