<?php

namespace Tourze\TestPaperBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;
use Tourze\TestPaperBundle\Repository\TemplateRuleRepository;

/**
 * @internal
 */
#[CoversClass(TemplateRuleRepository::class)]
#[RunTestsInSeparateProcesses]
final class TemplateRuleRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): TemplateRule
    {
        $templateRule = new TemplateRule();

        // 需要创建关联的 PaperTemplate
        $template = new PaperTemplate();
        $template->setName('测试模板_' . uniqid());
        $template->setDescription('测试模板描述');
        $template->setTotalQuestions(20);
        $template->setTotalScore(100);
        $template->setPassScore(60);
        $template->setIsActive(true);
        $template->setShuffleQuestions(false);
        $template->setShuffleOptions(false);
        $template->setCreateTime(new \DateTimeImmutable());
        $template->setUpdateTime(new \DateTimeImmutable());
        $template->setCreatedBy('test-user');
        $template->setUpdatedBy('test-user');

        $templateRule->setTemplate($template);
        $templateRule->setCategoryId('test-category-123');
        $templateRule->setQuestionType('single_choice');
        $templateRule->setDifficulty('medium');
        $templateRule->setQuestionCount(5);
        $templateRule->setScorePerQuestion(10);
        $templateRule->setSort(1);
        $templateRule->setExcludeUsed(false);
        $templateRule->setTagFilters(['tag1' => 'value1', 'tag2' => 'value2']);

        return $templateRule;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    protected function getRepository(): TemplateRuleRepository
    {
        return self::getService(TemplateRuleRepository::class);
    }

    public function testFindByTemplate(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getTemplate());
        $em->flush();

        $results = $this->getRepository()->findByTemplate($entity->getTemplate());
        $this->assertIsArray($results);
    }

    public function testReorderRules(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getTemplate());
        $em->flush();

        // 测试重新排序不会抛出异常
        $this->getRepository()->reorderRules($entity->getTemplate(), ['non-existent-id']);
        $this->assertTrue(true); // 如果没有异常就算成功
    }
}
