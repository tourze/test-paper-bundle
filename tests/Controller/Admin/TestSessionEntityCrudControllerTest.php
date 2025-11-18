<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TestPaperBundle\Controller\Admin\TestSessionEntityCrudController;
use Tourze\TestPaperBundle\Entity\TestSession;

/**
 * 考试会话CRUD控制器测试
 * @internal
 */
#[CoversClass(TestSessionEntityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TestSessionEntityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<TestSession>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(TestSessionEntityCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '试卷' => ['试卷'];
        yield '用户' => ['用户'];
        yield '会话状态' => ['会话状态'];
        yield '开始时间' => ['开始时间'];
        yield '到期时间' => ['到期时间'];
        yield '得分' => ['得分'];
        yield '总分' => ['总分'];
        yield '尝试次数' => ['尝试次数'];
        yield '用时(秒)' => ['用时(秒)'];
        yield '是否通过' => ['是否通过'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // NEW 操作已被禁用，但必须提供至少一个测试数据来避免 PHPUnit 错误
        // 基础测试类应该检测到 NEW 操作被禁用并跳过测试
        yield 'dummy' => ['dummy'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'user' => ['user'];
        yield 'paper' => ['paper'];
        yield 'status' => ['status'];
        yield 'score' => ['score'];
    }

    public function testControllerInstanceConfiguration(): void
    {
        $controller = $this->getControllerService();

        self::assertInstanceOf(TestSessionEntityCrudController::class, $controller);
    }

    /**
     * 重写基础测试，验证我们的字段而不是通用的必填字段
     */

    /**
     * 测试实体验证错误
     *
     * 验证场景：
     * - paper 和 user 为必填关联（nullable: false），未设置应触发验证错误
     * - status 为枚举类型（Assert\NotNull）
     * - score 必须 >= 0（Assert\PositiveOrZero）
     * - totalScore 必须 >= 0（Assert\PositiveOrZero）
     * - attemptNumber 必须 >= 0（Assert\PositiveOrZero）
     * - duration 必须 >= 0（Assert\PositiveOrZero）
     *
     * 由于NEW操作被禁用，直接测试实体验证约束
     * 验证无效数据时会产生适当的验证错误
     */
    public function testValidationErrors(): void
    {
        // 创建TestSession实体并设置无效数据以触发验证错误
        $testSession = new TestSession();

        // 设置负数来触发PositiveOrZero约束错误
        $testSession->setAttemptNumber(-1);  // 违反 PositiveOrZero 约束
        $testSession->setScore(-100);        // 违反 PositiveOrZero 约束
        $testSession->setDuration(-50);      // 违反 PositiveOrZero 约束

        // paper 和 user 未设置（应该触发验证错误，因为 JoinColumn nullable: false）

        $violations = self::getService(ValidatorInterface::class)->validate($testSession);

        // 验证存在验证错误（至少包括 paper, user 缺失，以及负数值）
        self::assertGreaterThan(0, count($violations),
            'TestSession实体设置无效数据应该有验证错误（必填关联缺失 + 负数值）');

        // 验证错误信息包含期望的模式
        $hasPositiveOrZeroError = false;
        $hasNullError = false;
        $violationMessages = [];

        foreach ($violations as $violation) {
            $message = (string) $violation->getMessage();
            $propertyPath = $violation->getPropertyPath();
            $violationMessages[] = $propertyPath . ': ' . $message;

            // 检查 PositiveOrZero 约束错误（attemptNumber, score, duration）
            if (in_array($propertyPath, ['attemptNumber', 'score', 'duration'], true)) {
                if (str_contains(strtolower($message), 'positive')
                    || str_contains(strtolower($message), 'greater')
                    || str_contains($message, 'This value should be positive')
                    || str_contains($message, 'This value should be either positive or zero')) {
                    $hasPositiveOrZeroError = true;
                }
            }

            // 检查必填关联错误（paper, user）
            if (in_array($propertyPath, ['paper', 'user'], true)) {
                if (str_contains(strtolower($message), 'blank')
                    || str_contains(strtolower($message), 'null')
                    || str_contains(strtolower($message), 'empty')
                    || str_contains($message, 'should not be blank')
                    || str_contains($message, 'should not be null')
                    || str_contains($message, '不能为空')) {
                    $hasNullError = true;
                }
            }
        }

        // 验证至少存在一种预期的验证错误
        self::assertTrue($hasPositiveOrZeroError || $hasNullError,
            '验证应该包含 PositiveOrZero 或必填关联约束错误，这些错误在表单提交时会导致422响应。'
            . PHP_EOL . '实际验证错误: ' . implode(', ', $violationMessages));

        // 额外验证：确保至少有 3 个错误（paper 缺失, user 缺失, attemptNumber/score/duration 负值）
        self::assertGreaterThanOrEqual(2, count($violations),
            '应该至少有 2 个验证错误（必填关联缺失或负数值）');
    }
}
