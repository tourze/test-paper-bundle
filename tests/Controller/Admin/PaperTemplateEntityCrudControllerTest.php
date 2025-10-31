<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TestPaperBundle\Controller\Admin\PaperTemplateEntityCrudController;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

/**
 * 试卷模板CRUD控制器测试
 * @internal
 */
#[CoversClass(PaperTemplateEntityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class PaperTemplateEntityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<PaperTemplate>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(PaperTemplateEntityCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '模板名称' => ['模板名称'];
        yield '模板描述' => ['模板描述'];
        yield '总题数' => ['总题数'];
        yield '总分' => ['总分'];
        yield '及格分数' => ['及格分数'];
        yield '考试时长（分钟）' => ['考试时长（分钟）'];
        yield '打乱题目顺序' => ['打乱题目顺序'];
        yield '打乱选项顺序' => ['打乱选项顺序'];
        yield '是否启用' => ['是否启用'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'totalQuestions' => ['totalQuestions'];
        yield 'totalScore' => ['totalScore'];
        yield 'passScore' => ['passScore'];
        yield 'isActive' => ['isActive'];
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = $this->getControllerService()::getEntityFqcn();

        self::assertSame(PaperTemplate::class, $fqcn);
    }

    public function testControllerInstanceConfiguration(): void
    {
        $controller = $this->getControllerService();

        self::assertInstanceOf(PaperTemplateEntityCrudController::class, $controller);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'totalQuestions' => ['totalQuestions'];
        yield 'totalScore' => ['totalScore'];
        yield 'passScore' => ['passScore'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * 重写基础测试，验证我们的字段而不是通用的必填字段
     */

    /**
     * 测试表单验证错误 - 提交无效数据应该显示验证错误
     *
     * 验证场景：
     * - name 为必填字段（Assert\NotBlank）
     * - passScore 必须在 0-100 之间（Assert\Range(min: 0, max: 100)）
     * - totalQuestions 必须 >= 0（Assert\PositiveOrZero）
     * - totalScore 必须 >= 0（Assert\PositiveOrZero）
     * - timeLimit 必须 >= 0（Assert\PositiveOrZero）
     *
     * 注意：避免处理复杂的JSON字段，专注于基本验证规则测试
     */
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 获取表单并设置无效数据来触发验证错误
        $form = $crawler->selectButton('Create')->form();
        $entityName = $this->getEntitySimpleName();

        // 设置无效的数值来触发验证错误
        // name 留空（应该触发 NotBlank 验证错误）
        $form[$entityName . '[name]'] = '';

        // 设置无效的数值范围
        $form[$entityName . '[passScore]'] = '101';  // 违反最大值约束（应该 <= 100）
        $form[$entityName . '[totalQuestions]'] = '-1';  // 违反 PositiveOrZero 约束
        $form[$entityName . '[totalScore]'] = '-10';  // 违反 PositiveOrZero 约束

        // 设置时间限制无效值（如果字段存在）
        $fields = $form->getPhpValues();
        $entityFields = $fields[$entityName] ?? null;
        if (is_array($entityFields) && isset($entityFields['timeLimit'])) {
            $form[$entityName . '[timeLimit]'] = '-1';  // 违反 PositiveOrZero 约束
        }

        // 显式不设置JSON字段，避免CodeEditorField的类型转换问题
        // difficultyDistribution 和 questionTypeDistribution 在控制器中已修复

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

        // 验证具体约束：name 必填，passScore 超出范围，totalQuestions/totalScore 为负
        // 检查错误文本中是否包含预期的验证相关关键词
        $errorTextLower = strtolower($errorText);
        $hasExpectedError = str_contains($errorTextLower, 'required')
                            || str_contains($errorTextLower, 'blank')
                            || str_contains($errorTextLower, 'must')
                            || str_contains($errorTextLower, 'should')  // "This value should be..."
                            || str_contains($errorTextLower, 'invalid')
                            || str_contains($errorTextLower, 'error')
                            || str_contains($errorTextLower, 'range')
                            || str_contains($errorTextLower, 'positive');

        self::assertTrue($hasExpectedError,
            '应该显示 NotBlank、Range 或 PositiveOrZero 验证错误。实际错误文本: ' . substr($errorText, 0, 200));
    }
}
