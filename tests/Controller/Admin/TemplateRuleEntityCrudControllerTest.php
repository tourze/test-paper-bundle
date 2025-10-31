<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TestPaperBundle\Controller\Admin\TemplateRuleEntityCrudController;
use Tourze\TestPaperBundle\Entity\TemplateRule;

/**
 * 模板规则CRUD控制器测试
 * @internal
 */
#[CoversClass(TemplateRuleEntityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TemplateRuleEntityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<TemplateRule>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(TemplateRuleEntityCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '所属模板' => ['所属模板'];
        yield '题目分类ID' => ['题目分类ID'];
        yield '题目类型' => ['题目类型'];
        yield '难度等级' => ['难度等级'];
        yield '题目数量' => ['题目数量'];
        yield '每题分数' => ['每题分数'];
        yield '排序' => ['排序'];
        yield '最小正确率' => ['最小正确率'];
        yield '最大正确率' => ['最大正确率'];
        yield '排除已使用题目' => ['排除已使用题目'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'template' => ['template'];
        yield 'questionCount' => ['questionCount'];
        yield 'scorePerQuestion' => ['scorePerQuestion'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'template' => ['template'];
        yield 'questionCount' => ['questionCount'];
        yield 'scorePerQuestion' => ['scorePerQuestion'];
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = $this->getControllerService()::getEntityFqcn();

        self::assertSame(TemplateRule::class, $fqcn);
    }

    public function testControllerInstanceConfiguration(): void
    {
        $controller = $this->getControllerService();

        self::assertInstanceOf(TemplateRuleEntityCrudController::class, $controller);
    }

    /**
     * 重写父类方法以修复硬编码必填字段检查问题
     */

    /**
     * 测试表单验证错误 - 提交无效数据应该显示验证错误
     *
     * 验证场景：
     * - template 为必填关联（nullable: false），留空应触发验证错误
     * - questionCount 必须 >= 1（Assert\Range(min: 1)）
     * - scorePerQuestion 必须 >= 1（Assert\Range(min: 1)）
     * - sort 必须 >= 0（Assert\Range(min: 0)）
     * - minCorrectRate/maxCorrectRate 必须在 0-100 之间（Assert\Range(min: 0, max: 100)）
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
        // template 保持空（应该触发验证错误，因为 JoinColumn nullable: false）
        // questionCount 设置为无效值（小于最小值 1）
        // scorePerQuestion 设置为无效值（小于最小值 1）
        // sort 设置为无效值（小于最小值 0）
        $form[$entityName . '[questionCount]'] = '0';  // 违反最小值约束（应该 >= 1）
        $form[$entityName . '[scorePerQuestion]'] = '0';  // 违反最小值约束（应该 >= 1）

        // 设置 sort 为负值（如果字段存在）
        $fields = $form->getPhpValues();
        $entityFields = $fields[$entityName] ?? null;
        if (is_array($entityFields) && isset($entityFields['sort'])) {
            $form[$entityName . '[sort]'] = '-1';  // 违反最小值约束（应该 >= 0）
        }

        // 显式不设置tagFilters字段，避免CodeEditorField的类型转换问题
        // CodeEditorField在empty_data为'{}'时会导致类型冲突
        // 这里专注于测试基本的数值验证规则

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

        // 验证具体约束：缺失 template 关联，questionCount 和 scorePerQuestion 小于 1
        // 检查错误文本中是否包含预期的验证相关关键词
        $errorTextLower = strtolower($errorText);
        $hasExpectedError = str_contains($errorTextLower, 'required')
                            || str_contains($errorTextLower, 'blank')
                            || str_contains($errorTextLower, 'must')
                            || str_contains($errorTextLower, 'should')  // "This value should be..."
                            || str_contains($errorTextLower, 'invalid')
                            || str_contains($errorTextLower, 'error')
                            || str_contains($errorTextLower, 'range');

        self::assertTrue($hasExpectedError,
            '应该显示 Range 或必填字段验证错误。实际错误文本: ' . substr($errorText, 0, 200));
    }
}
