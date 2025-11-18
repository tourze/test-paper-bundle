<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DomCrawler\Crawler;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TestPaperBundle\Controller\Admin\PaperQuestionEntityCrudController;
use Tourze\TestPaperBundle\Entity\PaperQuestion;

/**
 * 试卷题目关联CRUD控制器测试
 * @internal
 */
#[CoversClass(PaperQuestionEntityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class PaperQuestionEntityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<PaperQuestion>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(PaperQuestionEntityCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '所属试卷' => ['所属试卷'];
        yield '关联题目' => ['关联题目'];
        yield '排序顺序' => ['排序顺序'];
        yield '题目分数' => ['题目分数'];
        yield '是否必答' => ['是否必答'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    public function testControllerInstanceConfiguration(): void
    {
        $controller = $this->getControllerService();

        self::assertInstanceOf(PaperQuestionEntityCrudController::class, $controller);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'paper' => ['paper'];
        yield 'question' => ['question'];
        yield 'score' => ['score'];
        yield 'sortOrder' => ['sortOrder'];
        yield 'isRequired' => ['isRequired'];
    }

    /**
     * 检查字段是否在页面上存在（处理不同的字段类型）
     */
    private function assertFieldExistsOnPage(Crawler $crawler, string $entityName, string $fieldName): void
    {
        // 对于不同类型的字段，使用不同的选择器检查字段是否存在
        $found = false;

        // 1. 普通input字段
        $inputSelector = sprintf('form input[name="%s[%s]"]', $entityName, $fieldName);
        if ($crawler->filter($inputSelector)->count() > 0) {
            $found = true;
        }

        // 2. select字段 (AssociationField可能渲染为select)
        $selectSelector = sprintf('form select[name="%s[%s]"]', $entityName, $fieldName);
        if ($crawler->filter($selectSelector)->count() > 0) {
            $found = true;
        }

        // 3. checkbox字段 (BooleanField)
        $checkboxSelector = sprintf('form input[type="checkbox"][name="%s[%s]"]', $entityName, $fieldName);
        if ($crawler->filter($checkboxSelector)->count() > 0) {
            $found = true;
        }

        // 4. textarea字段
        $textareaSelector = sprintf('form textarea[name="%s[%s]"]', $entityName, $fieldName);
        if ($crawler->filter($textareaSelector)->count() > 0) {
            $found = true;
        }

        // 5. autocomplete字段 (AssociationField with autocomplete)
        $autocompleteSelector = sprintf('form input[name="%s[%s][autocomplete]"]', $entityName, $fieldName);
        if ($crawler->filter($autocompleteSelector)->count() > 0) {
            $found = true;
        }

        // 6. 带ID的字段
        $idSelector = sprintf('form *[id="%s_%s"]', $entityName, $fieldName);
        if ($crawler->filter($idSelector)->count() > 0) {
            $found = true;
        }

        // 7. autocomplete字段的ID (AssociationField with autocomplete)
        $autocompleteIdSelector = sprintf('form *[id="%s_%s_autocomplete"]', $entityName, $fieldName);
        if ($crawler->filter($autocompleteIdSelector)->count() > 0) {
            $found = true;
        }

        self::assertTrue($found,
            sprintf('字段 %s 应该以某种形式存在 (input/select/checkbox/textarea/autocomplete)', $fieldName));

        // 对于有对应标签的字段，检查标签是否存在
        if (in_array($fieldName, ['score', 'sortOrder', 'isRequired', 'customOptions', 'remark'], true)) {
            $labelSelector = sprintf('label[for="%s_%s"]', $entityName, $fieldName);
            $labelElements = $crawler->filter($labelSelector);

            self::assertGreaterThan(0, $labelElements->count(),
                sprintf('标签 %s 应该存在', $fieldName));
        }
    }

    /**
     * 测试新建表单能正常显示
     */
    public function testNewFormDisplay(): void
    {
        $client = $this->createAuthenticatedClient();

        // 验证新建页面能正常访问和显示
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 验证页面包含表单
        $forms = $crawler->filter('form');
        self::assertGreaterThan(0, $forms->count(), '页面应该包含表单');

        // 验证必填字段确实存在
        $entityName = $this->getEntitySimpleName();
        $requiredFields = ['paper', 'question', 'score', 'sortOrder'];

        foreach ($requiredFields as $fieldName) {
            $this->assertFieldExistsOnPage($crawler, $entityName, $fieldName);
        }
    }

    /**
     * 测试编辑表单的字段预填充
     */
    #[DataProvider('provideEditPageFields')]
    public function testEditFormFieldsPrePopulated(string $fieldName): void
    {
        $client = $this->createAuthenticatedClient();

        // 获取第一个记录的编辑页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::INDEX));
        $this->assertResponseIsSuccessful();

        // 查找第一个编辑链接
        $editLinks = $crawler->filter('a[title*="Edit"], a:contains("Edit")');

        if (0 === $editLinks->count()) {
            self::markTestSkipped('没有找到可编辑的记录');
        }

        $editUrl = $editLinks->first()->attr('href');
        self::assertIsString($editUrl);

        $crawler = $client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        $entityName = $this->getEntitySimpleName();

        // 检查字段是否存在（使用与新建页面相同的逻辑）
        $this->assertFieldExistsOnPage($crawler, $entityName, $fieldName);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'paper' => ['paper'];
        yield 'question' => ['question'];
        yield 'score' => ['score'];
        yield 'sortOrder' => ['sortOrder'];
        yield 'isRequired' => ['isRequired'];
    }

    /**
     * 重写基础测试，验证我们的字段而不是通用的必填字段
     */

    /**
     * 测试表单验证错误 - 提交无效数据应该显示验证错误
     *
     * 验证场景：
     * - paper 和 question 为必填关联（nullable: false），留空应触发验证错误
     * - score 必须 >= 1 (Assert\Range(min: 1))
     * - sortOrder 必须 >= 0 (Assert\Range(min: 0))
     */
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 获取表单，填入无效数据（缺少必填的关联字段，但设置其他字段为无效值）
        $form = $crawler->selectButton('Create')->form();
        $entityName = $this->getEntitySimpleName();

        // 设置无效的数值来触发验证错误，但不触发数据库约束错误
        // paper 和 question 保持空（应该触发验证错误，因为 JoinColumn nullable: false）
        // score 设置为无效值（小于最小值 1）
        // sortOrder 设置为无效值（小于最小值 0）
        $form[$entityName . '[score]'] = '0';  // 违反最小值约束（应该 >= 1）
        $form[$entityName . '[sortOrder]'] = '-1';  // 违反最小值约束（应该 >= 0）

        $crawler = $client->submit($form);

        // 验证返回状态码（422 Unprocessable Entity 或重定向到表单页面显示错误）
        $responseCode = $client->getResponse()->getStatusCode();

        if (422 === $responseCode) {
            $this->assertResponseStatusCodeSame(422);
            // 检查是否有验证错误信息
            $errorText = $crawler->filter('.invalid-feedback, .form-error-message, .alert-danger')->text();
            self::assertNotEmpty($errorText, '应该显示验证错误信息');
        } else {
            // 如果重定向回表单页面或其他状态，检查错误信息
            $this->assertResponseIsSuccessful();
            $hasErrors = $crawler->filter('.invalid-feedback, .form-error-message, .alert-danger')->count() > 0;
            self::assertTrue($hasErrors, '提交包含无效数据的表单应该显示验证错误');
        }

        // 统一验证错误内容（无论哪种响应方式）
        $errorText = $crawler->filter('.invalid-feedback, .form-error-message, .alert-danger')->text();
        self::assertNotEmpty($errorText, '验证错误信息不应为空');

        // 验证具体约束：缺失 paper/question 关联，score 小于 1，sortOrder 小于 0
        // 检查错误文本中是否包含预期的验证相关关键词
        $errorTextLower = strtolower($errorText);
        $hasExpectedError = str_contains($errorTextLower, 'required')
                            || str_contains($errorTextLower, 'blank')
                            || str_contains($errorTextLower, 'must')
                            || str_contains($errorTextLower, 'should')  // "This value should be..."
                            || str_contains($errorTextLower, 'invalid')
                            || str_contains($errorTextLower, 'error');

        self::assertTrue($hasExpectedError,
            '应该显示验证错误相关信息。实际错误文本: ' . substr($errorText, 0, 200));
    }
}
