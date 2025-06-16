# TestPaperBundle

试卷管理和考试模块 - 与 question-bank-bundle 紧密集成

## 核心定位

TestPaperBundle 专注于**试卷管理**和**考试流程**，通过与 question-bank-bundle 集成来实现完整的考试系统。

### 职责划分

- **question-bank-bundle**：管理题目、分类、标签
- **test-paper-bundle**：管理试卷、组卷、考试、评分

## 主要功能

### 1. 试卷管理
- 创建和管理试卷
- 设置考试参数（时长、及格分、重考次数等）
- 试卷状态管理（草稿、发布、归档）

### 2. 组卷功能
- **手动组卷**：手动选择题目添加到试卷
- **模板组卷**：按照预设规则自动选题
- **随机组卷**：根据条件随机抽取题目
- **标签组卷**：基于题目标签生成试卷

### 3. 考试管理
- 考试会话（TestSession）管理
- 答题过程控制和计时
- 防作弊措施（题目/选项随机）

### 4. 自动评分
- 客观题自动评分
- 详细的成绩分析
- 多维度统计报告

## 安装配置

### 1. 安装
```bash
composer require tourze/test-paper-bundle
```

### 2. 配置依赖
确保已经安装并配置了 `tourze/question-bank-bundle`。

### 3. 注册Bundle
```php
// config/bundles.php
return [
    // ...
    Tourze\QuestionBankBundle\QuestionBankBundle::class => ['all' => true],
    Tourze\TestPaperBundle\TestPaperBundle::class => ['all' => true],
];
```

### 4. 生成数据库表
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 使用示例

### 创建试卷

```php
use Tourze\TestPaperBundle\Service\PaperService;

// 创建空白试卷
$paper = $paperService->createPaper(
    title: '2024年春季期末考试',
    description: '高等数学期末考试',
    timeLimit: 7200,  // 2小时
    passScore: 60     // 及格分60
);

// 设置试卷参数
$paper->setAllowRetake(true);
$paper->setMaxAttempts(3);
$paper->setShowResults(true);
$paper->setShowAnswers(false);
```

### 手动添加题目

```php
use Tourze\QuestionBankBundle\Service\QuestionService;

// 从题库获取题目
$questions = $questionService->findByCriteria([
    'category' => $categoryId,
    'type' => 'single_choice',
    'difficulty' => 'medium'
]);

// 添加题目到试卷
foreach ($questions as $question) {
    $paperService->addQuestion(
        paper: $paper,
        question: $question,
        score: 5,
        sortOrder: $sortOrder++
    );
}

// 发布试卷
$paperService->publishPaper($paper);
```

### 模板组卷

```php
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

// 创建组卷模板
$template = new PaperTemplate();
$template->setName('标准期末考试模板');
$template->setDescription('适用于期末考试的标准模板');
$template->setTotalQuestions(30);
$template->setTotalScore(100);
$template->setTimeLimit(7200);

// 添加组卷规则
$rule1 = new TemplateRule();
$rule1->setTemplate($template);
$rule1->setCategoryId($mathCategoryId);
$rule1->setQuestionType('single_choice');
$rule1->setDifficulty('easy');
$rule1->setQuestionCount(10);
$rule1->setScorePerQuestion(3);

$template->addRule($rule1);

// 根据模板生成试卷
$paper = $paperGeneratorService->generateFromTemplate($template);
```

### 随机组卷

```php
use Tourze\TestPaperBundle\Service\PaperGeneratorService;

// 定义题型分布
$typeDistribution = [
    'single_choice' => 60,   // 60%
    'multiple_choice' => 30, // 30%
    'true_false' => 10,      // 10%
];

// 定义难度分布
$difficultyDistribution = [
    'easy' => 30,    // 30%
    'medium' => 50,  // 50%
    'hard' => 20,    // 20%
];

// 生成随机试卷
$paper = $paperGeneratorService->generateRandom(
    categoryIds: [$category1Id, $category2Id],
    questionCount: 30,
    typeDistribution: $typeDistribution,
    difficultyDistribution: $difficultyDistribution,
    timeLimit: 5400,  // 90分钟
    title: '随机练习卷'
);
```

### 考试流程

```php
use Tourze\TestPaperBundle\Service\TestSessionService;

// 创建考试会话
$session = $testSessionService->createSession($paper, $user);

// 开始考试
$session = $testSessionService->startSession($session);

// 答题
foreach ($questions as $question) {
    // 记录开始答题时间
    $session->startQuestionTiming($question->getUuid());
    
    // 提交答案
    $answer = $_POST['answer']; // 从表单获取
    $testSessionService->submitAnswer($session, $question->getUuid(), $answer);
}

// 完成考试
$session = $testSessionService->completeSession($session);

// 获取成绩
$score = $session->getScore();
$passed = $session->isPassed();
```

### 成绩分析

```php
use Tourze\TestPaperBundle\Service\PaperScoringService;

// 获取详细成绩
$results = $paperScoringService->getDetailedResults($session);
/*
返回结构：
[
    'results' => [
        [
            'question' => Question实例,
            'userAnswer' => 用户答案,
            'isCorrect' => 是否正确,
            'score' => 得分,
            'maxScore' => 满分
        ],
        ...
    ],
    'summary' => [
        'totalScore' => 85,
        'maxScore' => 100,
        'correctCount' => 25,
        'totalCount' => 30,
        'correctRate' => 83.33
    ]
]
*/

// 按题型统计
$typeStats = $paperScoringService->getScoreByType($session);
```

## 防作弊功能

### 题目顺序随机
```php
$paperService->shuffleQuestions($paper);
```

### 选项顺序随机
```php
$paperService->shuffleOptions($paper);
```

## 试卷管理

### 复制试卷
```php
$newPaper = $paperService->duplicatePaper($originalPaper, '副本 - ' . $originalPaper->getTitle());
```

### 试卷状态
```php
use Tourze\TestPaperBundle\Enum\PaperStatus;

// 发布试卷（允许考试）
$paperService->publishPaper($paper);

// 归档试卷（只读）
$paperService->archivePaper($paper);
```

## 实体结构

### 核心实体
- **TestPaper**: 试卷
- **PaperQuestion**: 试卷与题目的关联（引用 question-bank-bundle 的 Question）
- **TestSession**: 考试会话
- **PaperTemplate**: 组卷模板
- **TemplateRule**: 模板规则

### 重要字段说明

#### TestPaper
- `status`: 试卷状态（草稿/发布/归档）
- `generationType`: 生成方式（手动/模板/随机/智能）
- `timeLimit`: 考试时长（秒）
- `passScore`: 及格分数
- `allowRetake`: 是否允许重考
- `maxAttempts`: 最大尝试次数
- `shuffleQuestions`: 是否打乱题目顺序
- `shuffleOptions`: 是否打乱选项顺序

#### PaperQuestion
- `paper`: 关联的试卷
- `question`: 关联的题目（来自 question-bank-bundle）
- `score`: 该题分数
- `sortOrder`: 排序顺序
- `customOptions`: 自定义选项（用于选项随机化）

#### TestSession
- `paper`: 关联的试卷
- `user`: 考试用户
- `status`: 会话状态（待考/进行中/已完成/已放弃）
- `score`: 得分
- `answers`: 答案数据
- `questionTimings`: 每题用时记录

## 扩展开发

### 自定义评分逻辑

```php
class CustomScoringService extends PaperScoringService
{
    protected function evaluateAnswer($question, $userAnswer, ?array $customOptions = null): bool
    {
        // 实现自定义评分逻辑
        if ($question->getType() === 'custom_type') {
            // 自定义题型的评分
        }
        
        return parent::evaluateAnswer($question, $userAnswer, $customOptions);
    }
}
```

### 自定义组卷策略

```php
class CustomGeneratorService extends PaperGeneratorService
{
    public function generateByCustomRule(array $params): TestPaper
    {
        // 实现自定义组卷逻辑
    }
}
```

## 注意事项

1. **题目ID兼容性**：question-bank-bundle 使用 UUID，而 test-paper-bundle 使用雪花ID，在 PaperQuestion 中已处理好关联。

2. **题型和难度**：使用字符串存储以保持灵活性，避免与 question-bank-bundle 的枚举耦合。

3. **权限控制**：本模块不包含权限控制，需要在应用层实现。

4. **主观题评分**：简答题、论述题等主观题型需要人工评分，自动评分只支持客观题。

## 许可证
MIT License