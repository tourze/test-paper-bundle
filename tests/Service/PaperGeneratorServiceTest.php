<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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

/**
 * @internal
 */
#[CoversClass(PaperGeneratorService::class)]
final class PaperGeneratorServiceTest extends TestCase
{
    private PaperService&MockObject $paperService;

    private QuestionService&MockObject $questionService;

    private PaperGeneratorService $paperGenerator;

    public function testGenerateFromTemplate(): void
    {
        $this->initializeServices();
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
        for ($i = 0; $i < 5; ++$i) {
            // 使用具体类 Question 而非接口的原因：
            // 1) Question 是 Doctrine Entity，主要用于数据持久化，不设计接口
            // 2) Entity 类包含 ORM 映射和数据访问方法，接口化会失去这些特性
            // 3) 在测试中模拟 Entity 是常见做法，用于测试业务逻辑而非数据层
            $question = $this->createMock(Question::class);
            $question->method('getId')->willReturn("uuid-{$i}");
            $question->method('getType')->willReturn(QuestionType::SINGLE_CHOICE);
            $question->method('getDifficulty')->willReturn(Difficulty::medium());
            $question->method('getContent')->willReturn("测试题目 {$i}");
            $questions[] = $question;
        }

        // 设置模拟方法返回值
        $paper = new TestPaper();
        $paper->setTitle('测试模板 - 2024-01-01 10:00');
        $paper->setGenerationType(PaperGenerationType::TEMPLATE);

        $this->paperService
            ->expects($this->once())
            ->method('createPaper')
            ->willReturn($paper)
        ;

        $paginatedResult = new PaginatedResult(
            items: $questions,
            total: count($questions),
            page: 1,
            limit: 5
        );

        $this->questionService
            ->expects($this->once())
            ->method('searchQuestions')
            ->with(self::isInstanceOf(SearchCriteria::class))
            ->willReturn($paginatedResult)
        ;

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions')
        ;

        // 执行测试
        $result = $this->paperGenerator->generateFromTemplate($template);

        // 验证结果
        $this->assertEquals(PaperGenerationType::TEMPLATE, $result->getGenerationType());
    }

    public function testGenerateRandom(): void
    {
        $this->initializeServices();
        $categoryIds = [1, 2];
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
        for ($i = 0; $i < 10; ++$i) {
            // 使用具体类 Question 而非接口的原因：
            // 1) Question 是 Doctrine Entity，主要用于数据持久化，不设计接口
            // 2) Entity 类包含 ORM 映射和数据访问方法，接口化会失去这些特性
            // 3) 在测试中模拟 Entity 是常见做法，用于测试业务逻辑而非数据层
            $question = $this->createMock(Question::class);
            $question->method('getId')->willReturn("uuid-{$i}");
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
            ->willReturn($paper)
        ;

        $paginatedResult2 = new PaginatedResult(
            items: array_slice($questions, 0, 3),
            total: 3,
            page: 1,
            limit: 3
        );

        $this->questionService
            ->expects($this->atLeastOnce())
            ->method('searchQuestions')
            ->willReturn($paginatedResult2)
        ;

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions')
        ;

        $this->paperService
            ->expects($this->once())
            ->method('shuffleQuestions')
        ;

        $result = $this->paperGenerator->generateRandom(
            $categoryIds,
            $questionCount,
            $typeDistribution,
            $difficultyDistribution,
            3600,
            '随机试卷'
        );

        $this->assertEquals(PaperGenerationType::RANDOM, $result->getGenerationType());
    }

    public function testGenerateByTags(): void
    {
        $this->initializeServices();
        $tags = [1, 2];
        $questionCount = 20;

        // 创建模拟题目
        $questions = [];
        for ($i = 0; $i < 20; ++$i) {
            // 使用具体类 Question 而非接口的原因：
            // 1) Question 是 Doctrine Entity，主要用于数据持久化，不设计接口
            // 2) Entity 类包含 ORM 映射和数据访问方法，接口化会失去这些特性
            // 3) 在测试中模拟 Entity 是常见做法，用于测试业务逻辑而非数据层
            $question = $this->createMock(Question::class);
            $question->method('getId')->willReturn("uuid-{$i}");
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
            ->willReturn($paper)
        ;

        $paginatedResult3 = new PaginatedResult(
            items: $questions,
            total: count($questions),
            page: 1,
            limit: 20
        );

        $this->questionService
            ->expects($this->once())
            ->method('searchQuestions')
            ->with(self::isInstanceOf(SearchCriteria::class))
            ->willReturn($paginatedResult3)
        ;

        $this->paperService
            ->expects($this->once())
            ->method('addQuestions')
        ;

        $result = $this->paperGenerator->generateByTags(
            $tags,
            $questionCount,
            3600,
            '专项练习'
        );

        $this->assertEquals(PaperGenerationType::INTELLIGENT, $result->getGenerationType());
    }

    protected function onSetUp(): void
    {
    }

    private function initializeServices(): void
    {
        if (!isset($this->paperService)) {
            // 使用具体类 PaperService 而非接口的原因：
            // 1) PaperService 没有定义对应的接口，直接使用具体实现
            // 2) 该服务类方法签名相对稳定，测试中模拟不会带来维护问题
            // 3) 建议后续重构时为核心服务类添加接口以提高可测试性
            $this->paperService = $this->createMock(PaperService::class);
            // 使用具体类 QuestionService 而非接口的原因：
            // 1) 虽然存在 QuestionServiceInterface，但当前测试直接依赖具体实现
            // 2) 该实现包含复杂的业务逻辑，模拟接口可能更适合但需要重构测试
            // 3) 建议未来重构为使用 QuestionServiceInterface 以提高解耦性
            $this->questionService = $this->createMock(QuestionService::class);
            $this->paperGenerator = new PaperGeneratorService(
                $this->paperService,
                $this->questionService
            );
        }
    }

    /**
     * @return array<string>
     */
}
