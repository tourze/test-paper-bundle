<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;
use Tourze\TestPaperBundle\Service\PaperScoringService;

class PaperScoringServiceTest extends TestCase
{
    private PaperQuestionRepository $paperQuestionRepository;
    private PaperScoringService $scoringService;

    public function testCalculateScore(): void
    {
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
            ->willReturn($questions);

        $score = $this->scoringService->calculateScore($session);

        // 应该得18分（第一题5分，第二题10分，第三题3分）
        $this->assertEquals(18, $score);
    }

    private function createPaperQuestion(string $uuid, QuestionType $type, $correctAnswer, int $score): PaperQuestion
    {
        $question = $this->createMock(Question::class);
        $mockId = $this->createMock(\Symfony\Component\Uid\Uuid::class);
        $mockId->method('__toString')->willReturn($uuid);
        $question->method('getId')->willReturn($mockId);
        $question->method('getType')->willReturn($type);
        $question->method('retrieveApiArray')->willReturn([
            'correctLetters' => $correctAnswer
        ]);

        $paperQuestion = new PaperQuestion();
        $paperQuestion->setQuestion($question);
        $paperQuestion->setScore($score);

        return $paperQuestion;
    }

    public function testGetDetailedResults(): void
    {
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
            ->willReturn($questions);

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
            ->willReturn($questions);

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

    protected function setUp(): void
    {
        $this->paperQuestionRepository = $this->createMock(PaperQuestionRepository::class);
        $this->scoringService = new PaperScoringService(
            $this->paperQuestionRepository
        );
    }
}