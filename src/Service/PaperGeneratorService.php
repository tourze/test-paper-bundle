<?php

namespace Tourze\TestPaperBundle\Service;

use Tourze\QuestionBankBundle\DTO\SearchCriteria;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\QuestionBankBundle\Service\QuestionService;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;

/**
 * 试卷生成服务
 */
class PaperGeneratorService
{
    public function __construct(
        private readonly PaperService $paperService,
        private readonly QuestionService $questionService
    ) {
    }

    /**
     * 根据模板生成试卷
     */
    public function generateFromTemplate(PaperTemplate $template): TestPaper
    {
        $paper = $this->paperService->createPaper(
            $template->getName() . ' - ' . date('Y-m-d H:i'),
            $template->getDescription(),
            $template->getTimeLimit(),
            $template->getPassScore()
        );
        
        $paper->setGenerationType(PaperGenerationType::TEMPLATE);
        
        // 按照模板规则选题
        $selectedQuestions = [];
        
        foreach ($template->getRules() as $rule) {
            $criteria = new SearchCriteria();
            if ($rule->getCategoryId() !== null) {
                $criteria->setCategoryIds([$rule->getCategoryId()]);
            }
            if ($rule->getQuestionType() !== null) {
                $criteria->setTypes([QuestionType::from($rule->getQuestionType())]);
            }
            $criteria->setLimit($rule->getQuestionCount());
            
            $result = $this->questionService->searchQuestions($criteria);
            $questions = $result->getItems();
            
            foreach ($questions as $question) {
                $selectedQuestions[] = [
                    'question' => $question,
                    'score' => $rule->getScorePerQuestion(),
                ];
            }
        }
        
        // 添加题目到试卷
        $this->paperService->addQuestions($paper, $selectedQuestions);
        
        // 根据模板设置决定是否打乱
        if ($template->isShuffleQuestions()) {
            $this->paperService->shuffleQuestions($paper);
        }
        
        if ($template->isShuffleOptions()) {
            $this->paperService->shuffleOptions($paper);
        }
        
        return $paper;
    }

    /**
     * 随机生成试卷
     */
    public function generateRandom(
        array $categoryIds,
        int $questionCount,
        array $typeDistribution = [],
        array $difficultyDistribution = [],
        int $timeLimit = 3600,
        string $title = '随机试卷'
    ): TestPaper {
        $paper = $this->paperService->createPaper($title, '随机生成的试卷', $timeLimit);
        $paper->setGenerationType(PaperGenerationType::RANDOM);
        
        // 计算各种类型和难度的题目数量
        $typeQuestions = $this->calculateDistribution($questionCount, $typeDistribution);
        $difficultyQuestions = $this->calculateDistribution($questionCount, $difficultyDistribution);
        
        $selectedQuestions = [];
        
        // 按类型和难度组合选题
        foreach ($typeQuestions as $type => $typeCount) {
            foreach ($difficultyQuestions as $difficulty => $diffCount) {
                $count = (int) ceil($typeCount * $diffCount / $questionCount);
                
                $criteria = new SearchCriteria();
                $criteria->setCategoryIds($categoryIds);
                $criteria->setTypes([QuestionType::from($type)]);
                $criteria->setLimit($count);
                
                $result = $this->questionService->searchQuestions($criteria);
                $questions = $result->getItems();
                
                foreach ($questions as $question) {
                    $selectedQuestions[] = [
                        'question' => $question,
                        'score' => $this->calculateScore($question),
                    ];
                }
            }
        }
        
        // 如果题目不够，补充随机题目
        if (count($selectedQuestions) < $questionCount) {
            $remaining = $questionCount - count($selectedQuestions);
            $criteria = new SearchCriteria();
            $criteria->setCategoryIds($categoryIds);
            $criteria->setLimit($remaining);
            
            $result = $this->questionService->searchQuestions($criteria);
            $questions = $result->getItems();
            
            foreach ($questions as $question) {
                // 避免重复
                $exists = false;
                foreach ($selectedQuestions as $selected) {
                    if ($selected['question']->getId()->__toString() === $question->getId()->__toString()) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $selectedQuestions[] = [
                        'question' => $question,
                        'score' => $this->calculateScore($question),
                    ];
                }
            }
        }
        
        // 限制题目数量
        $selectedQuestions = array_slice($selectedQuestions, 0, $questionCount);
        
        // 添加题目到试卷
        $this->paperService->addQuestions($paper, $selectedQuestions);
        
        // 打乱题目顺序
        $this->paperService->shuffleQuestions($paper);
        
        return $paper;
    }

    /**
     * 计算分布
     */
    private function calculateDistribution(int $total, array $distribution): array
    {
        if (empty($distribution)) {
            return [];
        }

        $result = [];
        $sum = array_sum($distribution);

        if ($sum == 0) {
            return [];
        }

        $assigned = 0;
        foreach ($distribution as $key => $percentage) {
            $count = (int) round($total * $percentage / $sum);
            $result[$key] = $count;
            $assigned += $count;
        }

        // 处理舍入误差
        if ($assigned < $total) {
            $firstKey = array_key_first($result);
            $result[$firstKey] += $total - $assigned;
        }

        return $result;
    }

    /**
     * 计算题目分数（可以根据题型和难度设置不同分值）
     */
    private function calculateScore(Question $question): int
    {
        // 基础分值
        $baseScore = match ($question->getType()) {
            QuestionType::SINGLE_CHOICE, QuestionType::TRUE_FALSE => 3,
            QuestionType::MULTIPLE_CHOICE => 5,
            QuestionType::FILL_BLANK => 5,
            QuestionType::ESSAY => 20,
        };

        // 难度系数
        $difficulty = $question->getDifficulty();
        $difficultyFactor = match ($difficulty->getLevel()) {
            1, 2 => 0.8,  // easy, easier
            3 => 1.0,     // medium
            4, 5 => 1.2,  // hard, harder
            default => 1.0,
        };

        return (int) round($baseScore * $difficultyFactor);
    }

    /**
     * 根据标签生成试卷
     */
    public function generateByTags(
        array $tags,
        int $questionCount,
        int $timeLimit = 3600,
        string $title = '专项练习'
    ): TestPaper {
        $paper = $this->paperService->createPaper($title, '基于标签生成的试卷', $timeLimit);
        $paper->setGenerationType(PaperGenerationType::INTELLIGENT);

        $criteria = new SearchCriteria();
        $criteria->setTagIds($tags);
        $criteria->setLimit($questionCount);

        $result = $this->questionService->searchQuestions($criteria);
        $questions = $result->getItems();

        $selectedQuestions = [];
        foreach ($questions as $question) {
            $selectedQuestions[] = [
                'question' => $question,
                'score' => $this->calculateScore($question),
            ];
        }

        $this->paperService->addQuestions($paper, $selectedQuestions);

        return $paper;
    }
}