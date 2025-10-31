<?php

namespace Tourze\TestPaperBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\QuestionBankBundle\ValueObject\Difficulty;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

/**
 * @internal
 */
#[CoversClass(PaperQuestion::class)]
final class PaperQuestionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $paperQuestion = new PaperQuestion();

        // 设置必需的关联实体
        $paper = new TestPaper();
        $paper->setTitle('测试试卷 - PaperQuestion');
        $paperQuestion->setPaper($paper);

        $question = new Question();
        $question->setTitle('测试题目');
        $question->setContent('这是一个测试题目内容');
        $question->setType(QuestionType::SINGLE_CHOICE);
        $question->setDifficulty(Difficulty::medium());
        $paperQuestion->setQuestion($question);

        return $paperQuestion;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield ['sortOrder', 5];
        yield ['score', 10];
        // isRequired/setIsRequired - 移除此测试项，因为 AbstractEntityTestCase 无法正确处理 is 前缀的 boolean 属性
        yield ['remark', '测试备注'];
        yield ['customOptions', ['key1' => 'value1', 'key2' => 'value2']];
    }
}
