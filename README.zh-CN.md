# 试卷模块 (TestPaperBundle)

一个功能完整的 Symfony 试卷管理系统，支持多种组卷方式和智能化考试功能。

## 主要特性

### 🎯 多样化试卷类型
- **标准试卷**：基础试卷功能
- **练习试卷**：不限时间、可重复练习
- **考试试卷**：正式考试、时间限制、严格模式
- **自适应试卷**：根据能力动态调整难度

### 🤖 智能组卷算法
- **模板组卷**：基于预设规则自动生成
- **随机组卷**：按分布比例随机抽取
- **智能组卷**：知识点权重平衡
- **自适应组卷**：IRT算法动态调整

### 📝 丰富题型支持
支持10种常见题型，满足各种考试需求：
- 单选题、多选题、判断题
- 填空题、简答题、论述题
- 匹配题、排序题、完形填空、阅读理解

### 📊 完善统计分析
- 多维度成绩统计
- 知识点掌握度分析
- 试卷质量评估
- 学习进度跟踪

## 安装配置

### 1. 安装包
```bash
composer require tourze/test-paper-bundle
```

### 2. 注册Bundle
```php
// config/bundles.php
return [
    // ...
    Tourze\TestPaperBundle\TestPaperBundle::class => ['all' => true],
];
```

### 3. 数据库迁移
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 核心功能使用

### 智能组卷示例
```php
use Tourze\TestPaperBundle\Service\PaperGeneratorService;

// 模板组卷
$paper = $paperGenerator->generateFromTemplate($template);

// 随机组卷
$paper = $paperGenerator->generateRandom(
    $questionBank,
    '随机试卷',
    20,  // 题目数量
    $difficultyDistribution,
    $questionTypeDistribution
);

// 智能组卷
$paper = $paperGenerator->generateIntelligent(
    $questionBank,
    '智能试卷',
    $knowledgePointWeights
);
```

### 考试流程管理
```php
use Tourze\TestPaperBundle\Service\TestSessionService;

// 创建考试会话
$session = $testSessionService->createSession($paper, $user);

// 开始考试
$session = $testSessionService->startSession($session);

// 提交答案
$session = $testSessionService->submitAnswer($session, $questionId, $answer);

// 完成考试
$session = $testSessionService->completeSession($session);
```

### 成绩分析
```php
use Tourze\TestPaperBundle\Service\PaperScoringService;

// 获取详细结果
$results = $paperScoringService->getDetailedResults($session);

// 按知识点统计
$knowledgeStats = $paperScoringService->getScoreByKnowledgePoint($session);

// 按题型统计
$typeStats = $paperScoringService->getScoreByType($session);
```

## 高级特性

### 自适应考试
```php
$adaptivePaper = new AdaptivePaper();
$adaptivePaper->setInitialDifficulty('0.5');
$adaptivePaper->setCorrectThreshold(3);
$adaptivePaper->setIncorrectThreshold(2);
```

### 防作弊机制
```php
// 随机化题目和选项
$paper = $paperGenerator->randomizeQuestionOrder($paper);
$paper = $paperGenerator->randomizeOptions($paper);
```

## 参考文档

- [完整API文档](README.md)
- [开发指南](docs/development.md)
- [最佳实践](docs/best-practices.md)
