<?php

namespace Tourze\PHPUnitSymfonyUnitTest;

use League\ConstructFinder\ConstructFinder;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\PHPUnitBase\TestCaseHelper;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

abstract class AbstractDependencyInjectionExtensionTestCase extends TestCase
{
    /**
     * 这个场景，没必要使用 RunTestsInSeparateProcesses 注解的
     */
    #[Test]
    final public function testShouldNotHaveRunTestsInSeparateProcesses(): void
    {
        $reflection = new \ReflectionClass($this);
        $this->assertEmpty(
            $reflection->getAttributes(RunTestsInSeparateProcesses::class),
            get_class($this) . '这个测试用例，不应使用 RunTestsInSeparateProcesses 注解'
        );
    }

    #[Test]
    final public function testTestCaseMustBeFinal(): void
    {
        $reflection = new \ReflectionClass($this);
        $this->assertTrue(
            $reflection->isFinal(),
            get_class($this) . '测试类必须声明为 final'
        );
    }

    #[Test]
    final public function testExtendsCorrectBaseClass(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));
        $this->assertNotNull($className, '请使用 \PHPUnit\Framework\Attributes\CoversClass 注解声明当前的测试目标类');
        $this->assertTrue(
            is_subclass_of($className, AutoExtension::class),
            "{$className}必须继承" . AutoExtension::class,
        );
    }

    /**
     * 有一些固定的目录，是一定会注册成服务的
     *
     * @return iterable<string>
     */
    protected function provideServiceDirectories(): iterable
    {
        yield 'Controller';
        yield 'Command';
        yield 'Service';
        yield 'Repository';
        yield 'EventSubscriber';
        yield 'MessageHandler';
        yield 'Procedure';
        yield 'Twig';
    }

    /**
     * 有一些固定的目录，不能被注册为服务
     *
     * @return iterable<string>
     */
    protected function provideNonServiceDirectories(): iterable
    {
        yield 'Entity';
        yield 'DependencyInjection';
        yield 'Request';
        yield 'Param';
        yield 'Result';
        yield 'Exception';
    }

    /**
     * 确认这个服务配置是加载OK的
     */
    #[Test]
    final public function testLoadShouldRegisterServices(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));
        $this->assertNotNull($className, '请使用 \PHPUnit\Framework\Attributes\CoversClass 注解声明当前的测试目标类');
        /** @var class-string $className */
        $reflection = new \ReflectionClass($className);

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // Assert - 检查扩展加载成功
        $this->assertTrue($container->isTrackingResources());

        $fileName = $reflection->getFileName();
        $this->assertIsString($fileName);
        $sourceDir = \dirname(\dirname($fileName));

        foreach ($this->provideServiceDirectories() as $serviceDir) {
            if (!is_dir("{$sourceDir}/{$serviceDir}")) {
                continue;
            }
            $constructs = ConstructFinder::locatedIn("{$sourceDir}/{$serviceDir}")->findClasses();
            foreach ($constructs as $construct) {
                $reflection = new \ReflectionClass($construct->name());
                if ($reflection->isAbstract()) {
                    continue;
                }

                $this->assertTrue($container->hasDefinition($construct->name()), "应该注册 {$construct->name()} 服务，请检查服务配置文件");
            }
        }
    }

    /**
     * 一些固定目录不应该被注册为服务（避免误扫全量目录）
     */
    #[Test]
    final public function testLoadShouldNotRegisterServicesInNonServiceDirectories(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));
        $this->assertNotNull($className, '请使用 \PHPUnit\Framework\Attributes\CoversClass 注解声明当前的测试目标类');
        /** @var class-string $className */
        $reflection = new \ReflectionClass($className);

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // Assert - 检查扩展加载成功
        $this->assertTrue($container->isTrackingResources());

        $fileName = $reflection->getFileName();
        $this->assertIsString($fileName);
        $sourceDir = \dirname(\dirname($fileName));

        $registeredServiceClasses = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!is_string($class) && (class_exists($id) || interface_exists($id))) {
                $class = $id;
            }
            if (is_string($class)) {
                $registeredServiceClasses[$class] = true;
            }
        }

        foreach ($this->provideNonServiceDirectories() as $serviceDir) {
            if (!is_dir("{$sourceDir}/{$serviceDir}")) {
                continue;
            }

            $constructs = ConstructFinder::locatedIn("{$sourceDir}/{$serviceDir}")->findClasses();
            foreach ($constructs as $construct) {
                $this->assertArrayNotHasKey(
                    $construct->name(),
                    $registeredServiceClasses,
                    "不应注册 {$construct->name()} 服务，请检查服务配置文件"
                );
            }
        }
    }

    /**
     * 测试是否注入了正确的 LoggerInterface
     */
    #[Test]
    final public function testServiceInjectedLoggerMustUseWithMonologChannelAttribute(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // Assert - 检查扩展加载成功
        $this->assertTrue($container->isTrackingResources());

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if (null === $class || !is_string($class) || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            // 检查构造函数是否存在
            $constructor = $reflection->getConstructor();
            if (null === $constructor) {
                continue;
            }

            // 检查构造函数参数是否包含 LoggerInterface
            $hasLoggerInterface = false;
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType
                    && 'Psr\Log\LoggerInterface' === $type->getName()) {
                    $hasLoggerInterface = true;
                    break;
                }
            }

            // 如果使用了 LoggerInterface，检查是否有 WithMonologChannel 注解
            if ($hasLoggerInterface) {
                $attributes = $reflection->getAttributes('Monolog\Attribute\WithMonologChannel');
                $this->assertNotSame(
                    [],
                    $attributes,
                    sprintf(
                        "服务类 %s 的构造函数使用了 LoggerInterface，但未使用 WithMonologChannel 注解，请使用 `#[WithMonologChannel(channel: '%s')]`",
                        $class,
                        $extension->getAlias()
                    )
                );
            }
        }
    }

    /**
     * Bundle类本身，不应该是服务
     */
    #[Test]
    final public function noRegisteredServicesAreBundle(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));

        $extension = new $className();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);
        $this->assertNotEmpty($container->getDefinitions());

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!is_string($class) && class_exists($id)) {
                $class = $id;
            }

            if (!is_string($class) || (!class_exists($class) && !interface_exists($class))) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $this->assertFalse(
                $reflection->isSubclassOf('Symfony\Component\HttpKernel\Bundle\Bundle'),
                "Service '{$id}' with class '{$class}' should not in container, remove it from the container"
            );
        }
    }

    /**
     * Entity不应该被注册为服务
     */
    #[Test]
    final public function noRegisteredServicesAreEntity(): void
    {
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($this));

        $extension = new $className();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);
        $this->assertNotEmpty($container->getDefinitions());

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!is_string($class) && class_exists($id)) {
                $class = $id;
            }

            if (!is_string($class) || (!class_exists($class) && !interface_exists($class))) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $this->assertEmpty(
                $reflection->getAttributes('Doctrine\ORM\Mapping\Entity'),
                "Service '{$id}' with class '{$class}' should not in container, remove it from the container"
            );
        }
    }
}
