<?php

namespace Tourze\PHPUnitSymfonyUnitTest;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;
use Tourze\PHPUnitBase\TestCaseHelper;

abstract class AbstractEventTestCase extends TestCase
{
    /**
     * 这个场景，没必要使用 RunTestsInSeparateProcesses 注解的
     */
    #[Test]
    final public function testShouldNotHaveRunTestsInSeparateProcesses(): void
    {
        $reflection = new \ReflectionClass($this);
        $this->assertEmpty(
            $reflection->getAttributes('PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses'),
            get_class($this) . '这个测试用例，不应使用 RunTestsInSeparateProcesses 注解'
        );
    }

    #[Test]
    final public function testExtendsEvent(): void
    {
        $coverClass = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));
        $this->assertNotNull($coverClass, '请使用 \PHPUnit\Framework\Attributes\CoversClass 注解声明当前的测试目标类');
        /** @var class-string $coverClass */
        $reflection = new \ReflectionClass($coverClass);
        $this->assertTrue($reflection->isSubclassOf('Symfony\Contracts\EventDispatcher\Event'));
    }
}
