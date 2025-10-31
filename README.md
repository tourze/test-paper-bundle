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

A comprehensive Symfony bundle for test paper management and examination system. 
Integrates with question-bank-bundle to provide complete exam functionality.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Advanced Usage](#advanced-usage)
- [Advanced Features](#advanced-features)
- [Core Entities](#core-entities)
- [Contributing](#contributing)
- [License](#license)

## Features

### 📝 Test Paper Management
- Create and manage test papers
- Configure exam parameters (time limit, passing score, retake options)
- Paper status management (draft, published, archived)
- Paper duplication and versioning

### 🎯 Question Assembly
- **Manual Assembly**: Manually select questions for the paper
- **Template Assembly**: Auto-generate papers based on predefined rules
- **Random Assembly**: Randomly select questions based on criteria
- **Tag-based Assembly**: Generate papers based on question tags

### 🎮 Examination System
- Test session management
- Answer submission and timing control
- Anti-cheating measures (question/option randomization)
- Multiple attempts support

### 📊 Scoring & Analytics
- Automatic scoring for objective questions
- Detailed score analysis
- Multi-dimensional statistical reports
- Performance tracking

## Requirements

- PHP >= 8.1
- Symfony >= 7.3
- Doctrine ORM >= 3.0

## Installation

### 1. Install via Composer
```bash
composer require tourze/test-paper-bundle
```

### 2. Register the Bundle
```php
// config/bundles.php
return [
    // ...
    Tourze\QuestionBankBundle\QuestionBankBundle::class => ['all' => true],
    Tourze\TestPaperBundle\TestPaperBundle::class => ['all' => true],
];
```

### 3. Update Database Schema
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Configuration

### Basic Configuration

The bundle works out of the box with minimal configuration. However, you can customize its behavior 
by configuring services:

```yaml
# config/services.yaml
services:
    # Override default scoring behavior
    Tourze\TestPaperBundle\Service\PaperScoringService:
        arguments:
            $defaultPassingScore: 60
            $strictMode: true
            
    # Configure paper generation settings
    Tourze\TestPaperBundle\Service\PaperGeneratorService:
        arguments:
            $maxQuestionsPerPaper: 100
            $defaultShuffleQuestions: true
```

### Entity Configuration

All entities use Snowflake IDs and timestamps. No additional configuration required for basic usage.

### Repository Configuration

The bundle provides custom repositories that are automatically registered with Doctrine.

## Quick Start

### Create a Test Paper

```php
use Tourze\TestPaperBundle\Service\PaperService;

// Create a blank test paper
$paper = $paperService->createPaper(
    title: '2024 Spring Final Exam',
    description: 'Advanced Mathematics Final Exam',
    timeLimit: 7200,  // 2 hours
    passScore: 60     // Passing score 60
);

// Configure paper settings
$paper->setAllowRetake(true);
$paper->setMaxAttempts(3);
```

### Add Questions to Paper

```php
use Tourze\QuestionBankBundle\Entity\Question;

// Add questions to the paper
foreach ($questions as $question) {
    $paperService->addQuestion(
        paper: $paper,
        question: $question,
        score: 5,
        sortOrder: $sortOrder++
    );
}

// Publish the paper
$paperService->publishPaper($paper);
```

### Template-based Paper Generation

```php
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;
use Tourze\TestPaperBundle\Service\PaperGeneratorService;

// Create paper template
$template = new PaperTemplate();
$template->setName('Standard Final Exam Template');
$template->setDescription('Standard template for final exams');
$template->setTotalQuestions(30);
$template->setTotalScore(100);
$template->setTimeLimit(7200);

// Add template rules
$rule1 = new TemplateRule();
$rule1->setTemplate($template);
$rule1->setCategoryId($mathCategoryId);
$rule1->setQuestionType('single_choice');
$rule1->setDifficulty('easy');
$rule1->setQuestionCount(10);
$rule1->setScorePerQuestion(3);

$template->addRule($rule1);

// Generate paper from template
$paper = $paperGeneratorService->generateFromTemplate($template);
```

### Exam Session Management

```php
use Tourze\TestPaperBundle\Service\TestSessionService;

// Create exam session
$session = $testSessionService->createSession($paper, $user);

// Start exam
$session = $testSessionService->startSession($session);

// Submit answer
$testSessionService->submitAnswer($session, $questionUuid, $answer);

// Complete exam
$session = $testSessionService->completeSession($session);

// Get results
$score = $session->getScore();
$passed = $session->isPassed();
```

### Scoring & Analytics

```php
use Tourze\TestPaperBundle\Service\PaperScoringService;

// Get detailed results
$results = $paperScoringService->getDetailedResults($session);

// Get score by question type
$typeStats = $paperScoringService->getScoreByType($session);
```

## Advanced Usage

### Custom Scoring Rules

Implement custom scoring logic by extending the base scoring service:

```php
class CustomScoringService extends PaperScoringService
{
    protected function evaluateAnswer($question, $userAnswer, $customOptions): bool
    {
        // Custom evaluation logic
        return parent::evaluateAnswer($question, $userAnswer, $customOptions);
    }
}
```

### Batch Operations

Process multiple papers efficiently:

```php
// Bulk paper generation
$papers = $paperGeneratorService->generateBatch($templates, $count);

// Batch scoring
$results = $paperScoringService->scoreBatch($sessions);
```

### Event Listeners

Listen to paper events for custom workflows:

```php
#[AsEventListener(event: PaperPublishedEvent::class)]
class PaperPublishedListener
{
    public function onPaperPublished(PaperPublishedEvent $event): void
    {
        $paper = $event->getPaper();
        // Custom logic when paper is published
    }
}
```

## Advanced Features

### Anti-cheating Measures

```php
// Randomize question order
$paperService->shuffleQuestions($paper);

// Randomize option order
$paperService->shuffleOptions($paper);
```

### Paper Management

```php
// Duplicate paper
$newPaper = $paperService->duplicatePaper($originalPaper, 'Copy - ' . $originalPaper->getTitle());

// Publish paper
$paperService->publishPaper($paper);

// Archive paper
$paperService->archivePaper($paper);
```

## Core Entities

- **TestPaper**: Represents a test paper with questions and settings
- **PaperQuestion**: Links questions from question-bank-bundle to papers
- **TestSession**: Manages exam sessions and user attempts
- **PaperTemplate**: Templates for automatic paper generation
- **TemplateRule**: Rules for template-based paper generation

## Contributing

Please see [CONTRIBUTING.md](https://github.com/tourze/php-monorepo/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.