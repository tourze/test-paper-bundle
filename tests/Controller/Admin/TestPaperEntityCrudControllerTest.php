<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TestPaperBundle\Controller\Admin\TestPaperEntityCrudController;
use Tourze\TestPaperBundle\Entity\TestPaper;

/**
 * 试卷CRUD控制器测试
 * @internal
 */
#[CoversClass(TestPaperEntityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TestPaperEntityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<TestPaper>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(TestPaperEntityCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '试卷标题' => ['试卷标题'];
        yield '试卷描述' => ['试卷描述'];
        yield '试卷状态' => ['试卷状态'];
        yield '组卷方式' => ['组卷方式'];
        yield '总分' => ['总分'];
        yield '及格分数' => ['及格分数'];
        yield '考试时长（秒）' => ['考试时长（秒）'];
        yield '题目总数' => ['题目总数'];
        yield '随机排序题目' => ['随机排序题目'];
        yield '随机排序选项' => ['随机排序选项'];
        yield '允许重做' => ['允许重做'];
        yield '最大重做次数' => ['最大重做次数'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'description' => ['description'];
        yield 'status' => ['status'];
        yield 'generationType' => ['generationType'];
        yield 'totalScore' => ['totalScore'];
        yield 'passScore' => ['passScore'];
        yield 'timeLimit' => ['timeLimit'];
        yield 'questionCount' => ['questionCount'];
        yield 'randomizeQuestions' => ['randomizeQuestions'];
        yield 'randomizeOptions' => ['randomizeOptions'];
        yield 'allowRetake' => ['allowRetake'];
        yield 'maxAttempts' => ['maxAttempts'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'description' => ['description'];
        yield 'status' => ['status'];
        yield 'generationType' => ['generationType'];
        yield 'totalScore' => ['totalScore'];
        yield 'passScore' => ['passScore'];
        yield 'timeLimit' => ['timeLimit'];
        yield 'questionCount' => ['questionCount'];
        yield 'randomizeQuestions' => ['randomizeQuestions'];
        yield 'randomizeOptions' => ['randomizeOptions'];
        yield 'allowRetake' => ['allowRetake'];
        yield 'maxAttempts' => ['maxAttempts'];
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = $this->getControllerService()::getEntityFqcn();

        self::assertSame(TestPaper::class, $fqcn);
    }

    public function testControllerInstanceConfiguration(): void
    {
        $controller = $this->getControllerService();

        self::assertInstanceOf(TestPaperEntityCrudController::class, $controller);
    }

    /**
     * 重写基础测试，验证我们的字段而不是通用的必填字段
     */

    /**
     * 测试表单验证错误 - 提交无效数据应该显示验证错误
     *
     * 验证场景：
     * - title 为必填字段（Assert\NotBlank）
     * - status 和 generationType 为枚举类型（Assert\NotNull）
     * - totalScore 必须 >= 0（Assert\PositiveOrZero）
     * - passScore 必须 >= 0（Assert\PositiveOrZero）
     * - timeLimit 必须 >= 0（Assert\PositiveOrZero）
     * - questionCount 必须 >= 0（Assert\PositiveOrZero）
     * - maxAttempts 必须 >= 0（Assert\PositiveOrZero）
     *
     * 注意：避免枚举字段的类型错误，专注于基本验证规则测试
     */
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 获取表单并设置无效数据来触发验证错误
        $form = $crawler->selectButton('Create')->form();
        $entityName = $this->getEntitySimpleName();

        // 设置无效的数值来触发验证错误，但避免枚举类型错误
        // title 留空（应该触发 NotBlank 验证错误）
        $form[$entityName . '[title]'] = '';

        // 对于枚举字段（status, generationType），保持默认值或空值
        // 它们会触发 NotNull 验证错误或使用默认值

        // 设置无效的数值（违反 PositiveOrZero 约束）
        $form[$entityName . '[totalScore]'] = '-10';  // 违反 PositiveOrZero 约束
        $form[$entityName . '[passScore]'] = '-5';  // 违反 PositiveOrZero 约束
        $form[$entityName . '[questionCount]'] = '-1';  // 违反 PositiveOrZero 约束

        // 设置其他可选字段的无效值（如果字段存在）
        $fields = $form->getPhpValues();
        $entityFields = $fields[$entityName] ?? null;
        if (is_array($entityFields) && isset($entityFields['timeLimit'])) {
            $form[$entityName . '[timeLimit]'] = '-1';  // 违反 PositiveOrZero 约束
        }
        if (is_array($entityFields) && isset($entityFields['maxAttempts'])) {
            $form[$entityName . '[maxAttempts]'] = '-1';  // 违反 PositiveOrZero 约束
        }

        $crawler = $client->submit($form);

        // 验证返回状态码（422 Unprocessable Entity 或重定向到表单页面显示错误）
        $responseCode = $client->getResponse()->getStatusCode();

        if (422 === $responseCode) {
            $this->assertResponseStatusCodeSame(422);
        } else {
            // 如果重定向回表单页面或其他状态，检查错误信息
            $this->assertResponseIsSuccessful();
        }

        // 统一验证错误内容（无论哪种响应方式）
        $hasErrors = $crawler->filter('.invalid-feedback, .form-error-message, .alert-danger')->count() > 0;
        self::assertTrue($hasErrors, '提交包含无效数据的表单应该显示验证错误');

        $errorText = $crawler->filter('.invalid-feedback, .form-error-message, .alert-danger')->text();
        self::assertNotEmpty($errorText, '验证错误信息不应为空');

        // 验证具体约束：title 必填，数值字段为负
        // 检查错误文本中是否包含预期的验证相关关键词
        $errorTextLower = strtolower($errorText);
        $hasExpectedError = str_contains($errorTextLower, 'required')
                            || str_contains($errorTextLower, 'blank')
                            || str_contains($errorTextLower, 'must')
                            || str_contains($errorTextLower, 'should')  // "This value should be..."
                            || str_contains($errorTextLower, 'invalid')
                            || str_contains($errorTextLower, 'error')
                            || str_contains($errorTextLower, 'positive')
                            || str_contains($errorTextLower, 'greater');

        self::assertTrue($hasExpectedError,
            '应该显示 NotBlank 或 PositiveOrZero 验证错误。实际错误文本: ' . substr($errorText, 0, 200));
    }
}
