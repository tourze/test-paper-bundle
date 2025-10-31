# TestPaperBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/test-paper-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/test-paper-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/test-paper-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/test-paper-bundle)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)]
(https://www.php.net/)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)]
(https://github.com/tourze/php-monorepo)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)]
(https://codecov.io/gh/tourze/php-monorepo)

一个功能完整的 Symfony 试卷管理系统，支持多种组卷方式和智能化考试功能。
与 question-bank-bundle 紧密集成，提供完整的考试解决方案。

## 目录

- [主要特性](#主要特性)
- [系统要求](#系统要求)
- [安装](#安装)
- [配置](#配置)
- [快速开始](#快速开始)
- [高级用法](#高级用法)
- [高级特性](#高级特性)
- [核心实体](#核心实体)
- [贡献](#贡献)
- [许可证](#许可证)

## 主要特性

### 📝 试卷管理
- 创建和管理试卷
- 配置考试参数（时长、及格分、重考选项）
- 试卷状态管理（草稿、发布、归档）
- 试卷复制和版本管理

### 🎯 组卷功能
- **手动组卷**：手动选择题目添加到试卷
- **模板组卷**：基于预设规则自动生成试卷
- **随机组卷**：根据条件随机抽取题目
- **标签组卷**：基于题目标签生成试卷

### 🎮 考试系统
- 考试会话管理
- 答题提交和计时控制
- 防作弊措施（题目/选项随机化）
- 多次尝试支持

### 📊 评分分析
- 客观题自动评分
- 详细成绩分析
- 多维度统计报告
- 表现跟踪

## 系统要求

- PHP >= 8.1
- Symfony >= 7.3
- Doctrine ORM >= 3.0

## 安装

### 1. 通过 Composer 安装
```bash
composer require tourze/test-paper-bundle
```

### 2. 注册 Bundle
```php
// config/bundles.php
return [
    // ...
    Tourze\QuestionBankBundle\QuestionBankBundle::class => ['all' => true],
    Tourze\TestPaperBundle\TestPaperBundle::class => ['all' => true],
];
```

### 3. 更新数据库结构
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 配置

### 基础配置

Bundle 开箱即用，仅需最少配置。但您可以通过服务配置来自定义其行为：

```yaml
# config/services.yaml
services:
    # 覆盖默认评分行为
    Tourze\TestPaperBundle\Service\PaperScoringService:
        arguments:
            $defaultPassingScore: 60
            $strictMode: true
            
    # 配置试卷生成设置
    Tourze\TestPaperBundle\Service\PaperGeneratorService:
        arguments:
            $maxQuestionsPerPaper: 100
            $defaultShuffleQuestions: true
```

### 实体配置

所有实体都使用 Snowflake ID 和时间戳。基础使用无需额外配置。

### 仓储配置

Bundle 提供的自定义仓储会自动注册到 Doctrine。

## 快速开始

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

// 配置试卷设置
$paper->setAllowRetake(true);
$paper->setMaxAttempts(3);
```

### 添加题目到试卷

```php
use Tourze\QuestionBankBundle\Entity\Question;

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

### 基于模板的试卷生成

```php
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;
use Tourze\TestPaperBundle\Service\PaperGeneratorService;

// 创建试卷模板
$template = new PaperTemplate();
$template->setName('标准期末考试模板');
$template->setDescription('期末考试标准模板');
$template->setTotalQuestions(30);
$template->setTotalScore(100);
$template->setTimeLimit(7200);

// 添加模板规则
$rule1 = new TemplateRule();
$rule1->setTemplate($template);
$rule1->setCategoryId($mathCategoryId);
$rule1->setQuestionType('single_choice');
$rule1->setDifficulty('easy');
$rule1->setQuestionCount(10);
$rule1->setScorePerQuestion(3);

$template->addRule($rule1);

// 从模板生成试卷
$paper = $paperGeneratorService->generateFromTemplate($template);
```

### 考试会话管理

```php
use Tourze\TestPaperBundle\Service\TestSessionService;

// 创建考试会话
$session = $testSessionService->createSession($paper, $user);

// 开始考试
$session = $testSessionService->startSession($session);

// 提交答案
$testSessionService->submitAnswer($session, $questionUuid, $answer);

// 完成考试
$session = $testSessionService->completeSession($session);

// 获取结果
$score = $session->getScore();
$passed = $session->isPassed();
```

### 评分与分析

```php
use Tourze\TestPaperBundle\Service\PaperScoringService;

// 获取详细结果
$results = $paperScoringService->getDetailedResults($session);

// 按题型统计
$typeStats = $paperScoringService->getScoreByType($session);
```

## 高级用法

### 自定义评分规则

通过扩展基础评分服务实现自定义评分逻辑：

```php
class CustomScoringService extends PaperScoringService
{
    protected function evaluateAnswer($question, $userAnswer, $customOptions): bool
    {
        // 自定义评估逻辑
        return parent::evaluateAnswer($question, $userAnswer, $customOptions);
    }
}
```

### 批量操作

高效处理多个试卷：

```php
// 批量生成试卷
$papers = $paperGeneratorService->generateBatch($templates, $count);

// 批量评分
$results = $paperScoringService->scoreBatch($sessions);
```

### 事件监听器

监听试卷事件实现自定义工作流：

```php
#[AsEventListener(event: PaperPublishedEvent::class)]
class PaperPublishedListener
{
    public function onPaperPublished(PaperPublishedEvent $event): void
    {
        $paper = $event->getPaper();
        // 试卷发布时的自定义逻辑
    }
}
```

## 高级特性

### 防作弊措施

```php
// 随机化题目顺序
$paperService->shuffleQuestions($paper);

// 随机化选项顺序
$paperService->shuffleOptions($paper);
```

### 试卷管理

```php
// 复制试卷
$newPaper = $paperService->duplicatePaper($originalPaper, '副本 - ' . $originalPaper->getTitle());

// 发布试卷
$paperService->publishPaper($paper);

// 归档试卷
$paperService->archivePaper($paper);
```

## 核心实体

- **TestPaper**：表示包含题目和设置的试卷
- **PaperQuestion**：将 question-bank-bundle 中的题目链接到试卷
- **TestSession**：管理考试会话和用户尝试
- **PaperTemplate**：自动生成试卷的模板
- **TemplateRule**：基于模板的试卷生成规则

## 贡献

请参阅 [CONTRIBUTING.md](https://github.com/tourze/php-monorepo/blob/master/CONTRIBUTING.md) 了解详情。

## 许可证

MIT License。请参阅 [License File](LICENSE) 获取更多信息。
