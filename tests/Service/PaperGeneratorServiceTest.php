<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\QuestionBankBundle\DTO\SearchCriteria;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\QuestionBankBundle\Service\QuestionService;
use Tourze\QuestionBankBundle\ValueObject\Difficulty;
use Tourze\QuestionBankBundle\ValueObject\PaginatedResult;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Service\PaperGeneratorService;
use Tourze\TestPaperBundle\Service\PaperService;

class PaperGeneratorServiceTest extends TestCase
{
    private PaperService $paperService;
    private QuestionService $questionService;
    private PaperGeneratorService $paperGenerator;

    public function testGenerateFromTemplate(): void
    {
        // 创建测试模板
        $template = new PaperTemplate();
        $template->setName('测试模板');
        $template->setDescription('测试描述');
        $template->setTimeLimit(3600);
        $template->setPassScore(60);
        $template->setTotalQuestions(10);
        $template->setTotalScore(100);

        // 创建模板规则
        $rule = new TemplateRule();
        $rule->setTemplate($template);
        $rule->setCategoryId('math-category');
        $rule->setQuestionType('single_choice');
        $rule->setDifficulty('medium');
        $rule->setQuestionCount(5);
        $rule->setScorePerQuestion(10);

        $template->addRule($rule);

        // 创建模拟题目
        $questions = [];
        for ($i = 0; $i < 5; $i++) {
            $question = $this->createMock(Question::class);
            $mockId = $this->createMock(\Symfony\Component\Uid\Uuid::class);
            $mockId->method('__toString')->willReturn("uuid-$i");
            $question->method('getId')->willReturn($mockId);
            $question->method('getType')->willReturn(QuestionType::SINGLE_CHOICE);
            $question->method('getDifficulty')->willReturn(Difficulty::medium());
            $question->method('getContent')->willReturn("测试题目 $i");
            $questions[] = $question;
        }

        // 设置模拟方法返回值
        $paper = new TestPaper();
        $paper->setTitle('测试模板 - 2024-01-01 10:00');
        $paper->setGenerationType(PaperGenerationType::TEMPLATE);

        $this->paperService
            ->expects($this->once())
            ->method('createPaper')
            ->willReturn($paper);

        $paginatedResult = new PaginatedResult(
            items: $questions,
            total: count($questions),
            page: 1,
            limit: 5
        );

        $this->questionService
            ->expects($this->once())
            ->method('searchQuestions')
            ->with($this->isInstanceOf(SearchCriteria::class))
            ->willReturn($paginatedResult);

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions');

        // 执行测试
        $result = $this->paperGenerator->generateFromTemplate($template);

        // 验证结果
        $this->assertNotNull($result);
        $this->assertEquals(PaperGenerationType::TEMPLATE, $result->getGenerationType());
    }

    public function testGenerateRandom(): void
    {
        $categoryIds = ['math', 'physics'];
        $questionCount = 10;
        $typeDistribution = [
            'single_choice' => 60,
            'multiple_choice' => 40,
        ];
        $difficultyDistribution = [
            'easy' => 30,
            'medium' => 50,
            'hard' => 20,
        ];

        // 创建模拟题目
        $questions = [];
        for ($i = 0; $i < 10; $i++) {
            $question = $this->createMock(Question::class);
            $mockId = $this->createMock(\Symfony\Component\Uid\Uuid::class);
            $mockId->method('__toString')->willReturn("uuid-$i");
            $question->method('getId')->willReturn($mockId);
            $question->method('getType')->willReturn($i < 6 ? QuestionType::SINGLE_CHOICE : QuestionType::MULTIPLE_CHOICE);
            $question->method('getDifficulty')->willReturn(Difficulty::medium());
            $questions[] = $question;
        }

        $paper = new TestPaper();
        $paper->setTitle('随机试卷');
        $paper->setGenerationType(PaperGenerationType::RANDOM);

        $this->paperService
            ->expects($this->once())
            ->method('createPaper')
            ->willReturn($paper);

        $paginatedResult2 = new PaginatedResult(
            items: array_slice($questions, 0, 3),
            total: 3,
            page: 1,
            limit: 3
        );

        $this->questionService
            ->expects($this->atLeastOnce())
            ->method('searchQuestions')
            ->willReturn($paginatedResult2);

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions');

        $this->paperService
            ->expects($this->once())
            ->method('shuffleQuestions');

        $result = $this->paperGenerator->generateRandom(
            $categoryIds,
            $questionCount,
            $typeDistribution,
            $difficultyDistribution,
            3600,
            '随机试卷'
        );

        $this->assertNotNull($result);
        $this->assertEquals(PaperGenerationType::RANDOM, $result->getGenerationType());
    }

    public function testGenerateByTags(): void
    {
        $tags = ['重点', '高频'];
        $questionCount = 20;

        // 创建模拟题目
        $questions = [];
        for ($i = 0; $i < 20; $i++) {
            $question = $this->createMock(Question::class);
            $mockId = $this->createMock(\Symfony\Component\Uid\Uuid::class);
            $mockId->method('__toString')->willReturn("uuid-$i");
            $question->method('getId')->willReturn($mockId);
            $question->method('getType')->willReturn(QuestionType::SINGLE_CHOICE);
            $question->method('getDifficulty')->willReturn(Difficulty::medium());
            $questions[] = $question;
        }

        $paper = new TestPaper();
        $paper->setTitle('专项练习');
        $paper->setGenerationType(PaperGenerationType::INTELLIGENT);

        $this->paperService
            ->expects($this->once())
            ->method('createPaper')
            ->willReturn($paper);

        $paginatedResult3 = new PaginatedResult(
            items: $questions,
            total: count($questions),
            page: 1,
            limit: 20
        );

        $this->questionService
            ->expects($this->once())
            ->method('searchQuestions')
            ->with($this->isInstanceOf(SearchCriteria::class))
            ->willReturn($paginatedResult3);

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions');

        $result = $this->paperGenerator->generateByTags(
            $tags,
            $questionCount,
            3600,
            '专项练习'
        );

        $this->assertNotNull($result);
        $this->assertEquals(PaperGenerationType::INTELLIGENT, $result->getGenerationType());
    }

    protected function setUp(): void
    {
        $this->paperService = $this->createMock(PaperService::class);
        $this->questionService = $this->createMock(QuestionService::class);
        $this->paperGenerator = new PaperGeneratorService(
            $this->paperService,
            $this->questionService
        );
    }
}