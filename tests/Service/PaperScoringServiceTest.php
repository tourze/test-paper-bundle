<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;
use Tourze\TestPaperBundle\Service\PaperScoringService;

/**
 * @internal
 */
#[CoversClass(PaperScoringService::class)]
final class PaperScoringServiceTest extends TestCase
{
    private PaperQuestionRepository&MockObject $paperQuestionRepository;

    private PaperScoringService $scoringService;

    protected function onSetUp(): void
    {
    }

    public function testCalculateScore(): void
    {
        $this->initializeServices();
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');

        $session = new TestSession();
        $session->setPaper($paper);
        $session->setAnswers([
            'uuid-1' => 'A',
            'uuid-2' => ['A', 'B'],
            'uuid-3' => 'A',
        ]);

        // 创建题目和答案
        $questions = [
            $this->createPaperQuestion('uuid-1', QuestionType::SINGLE_CHOICE, ['A'], 5),
            $this->createPaperQuestion('uuid-2', QuestionType::MULTIPLE_CHOICE, ['A', 'B'], 10),
            $this->createPaperQuestion('uuid-3', QuestionType::TRUE_FALSE, ['A'], 3), // Assuming 'A' is false, 'B' is true
        ];

        $this->paperQuestionRepository
            ->expects($this->once())
            ->method('findByPaperWithQuestions')
            ->with($paper)
            ->willReturn($questions)
        ;

        $score = $this->scoringService->calculateScore($session);

        // 应该得18分（第一题5分，第二题10分，第三题3分）
        $this->assertEquals(18, $score);
    }

    /**
     * @param mixed $correctAnswer
     */
    private function createPaperQuestion(string $uuid, QuestionType $type, $correctAnswer, int $score): PaperQuestion
    {
        // 使用具体类 Question 而非接口的原因：
        // 1) Question 是 Doctrine Entity，主要用于数据持久化，不设计接口
        // 2) Entity 类包含 ORM 映射和数据访问方法，接口化会失去这些特性
        // 3) 在测试中模拟 Entity 是常见做法，用于测试业务逻辑而非数据层
        $question = $this->createMock(Question::class);
        $question->method('getId')->willReturn($uuid);
        $question->method('getType')->willReturn($type);
        $question->method('retrieveApiArray')->willReturn([
            'correctLetters' => $correctAnswer,
        ]);

        $paperQuestion = new PaperQuestion();
        $paperQuestion->setQuestion($question);
        $paperQuestion->setScore($score);

        return $paperQuestion;
    }

    public function testGetDetailedResults(): void
    {
        $this->initializeServices();
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');

        $session = new TestSession();
        $session->setPaper($paper);
        $session->setTotalScore(20);
        $session->setAnswers([
            'uuid-1' => 'A',
            'uuid-2' => 'B',
        ]);

        $questions = [
            $this->createPaperQuestion('uuid-1', QuestionType::SINGLE_CHOICE, ['A'], 10),
            $this->createPaperQuestion('uuid-2', QuestionType::SINGLE_CHOICE, ['C'], 10),
        ];

        $this->paperQuestionRepository
            ->expects($this->once())
            ->method('findByPaperWithQuestions')
            ->with($paper)
            ->willReturn($questions)
        ;

        $results = $this->scoringService->getDetailedResults($session);

        $this->assertArrayHasKey('results', $results);
        $this->assertArrayHasKey('summary', $results);

        $this->assertCount(2, $results['results']);
        $this->assertTrue($results['results'][0]['isCorrect']);
        $this->assertFalse($results['results'][1]['isCorrect']);

        $this->assertEquals(10, $results['summary']['totalScore']);
        $this->assertEquals(20, $results['summary']['maxScore']);
        $this->assertEquals(1, $results['summary']['correctCount']);
        $this->assertEquals(2, $results['summary']['totalCount']);
        $this->assertEquals(50, $results['summary']['correctRate']);
    }

    public function testGetScoreByType(): void
    {
        $this->initializeServices();
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');

        $session = new TestSession();
        $session->setPaper($paper);
        $session->setAnswers([
            'uuid-1' => 'A',
            'uuid-2' => 'B',
            'uuid-3' => ['A', 'B'],
        ]);

        $questions = [
            $this->createPaperQuestion('uuid-1', QuestionType::SINGLE_CHOICE, ['A'], 5),
            $this->createPaperQuestion('uuid-2', QuestionType::SINGLE_CHOICE, ['C'], 5),
            $this->createPaperQuestion('uuid-3', QuestionType::MULTIPLE_CHOICE, ['A', 'B'], 10),
        ];

        $this->paperQuestionRepository
            ->expects($this->once())
            ->method('findByPaperWithQuestions')
            ->with($paper)
            ->willReturn($questions)
        ;

        $typeStats = $this->scoringService->getScoreByType($session);

        $this->assertArrayHasKey('single_choice', $typeStats);
        $this->assertArrayHasKey('multiple_choice', $typeStats);

        // 单选题统计
        $singleChoice = $typeStats['single_choice'];
        $this->assertEquals(2, $singleChoice['totalQuestions']);
        $this->assertEquals(2, $singleChoice['answeredQuestions']);
        $this->assertEquals(1, $singleChoice['correctQuestions']);
        $this->assertEquals(5, $singleChoice['totalScore']);
        $this->assertEquals(10, $singleChoice['maxScore']);
        $this->assertEquals(50, $singleChoice['correctRate']);

        // 多选题统计
        $multipleChoice = $typeStats['multiple_choice'];
        $this->assertEquals(1, $multipleChoice['totalQuestions']);
        $this->assertEquals(1, $multipleChoice['answeredQuestions']);
        $this->assertEquals(1, $multipleChoice['correctQuestions']);
        $this->assertEquals(10, $multipleChoice['totalScore']);
        $this->assertEquals(10, $multipleChoice['maxScore']);
        $this->assertEquals(100, $multipleChoice['correctRate']);
    }

    private function initializeServices(): void
    {
        if (!isset($this->scoringService)) {
            // 使用具体类 PaperQuestionRepository 而非接口的原因：
            // 1) PaperQuestionRepository 继承自 Doctrine ServiceEntityRepository，没有接口设计
            // 2) Repository 类主要负责数据访问，直接模拟具体实现更符合测试目的
            // 3) Doctrine Repository 模式下，一般不为 Repository 设计接口，直接使用具体类
            $this->paperQuestionRepository = $this->createMock(PaperQuestionRepository::class);
            $this->scoringService = new PaperScoringService(
                $this->paperQuestionRepository
            );
        }
    }

    /**
     * @return array<string>
     */
}
