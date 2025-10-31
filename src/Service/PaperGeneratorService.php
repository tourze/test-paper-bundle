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
        private readonly QuestionService $questionService,
    ) {
    }

    /**
     * 根据模板生成试卷
     */
    public function generateFromTemplate(PaperTemplate $template): TestPaper
    {
        $paper = $this->paperService->createPaper(
            $template->getName() . ' - ' . date('Y-m-d H:i'),
            $template->getDescription() ?? '',
            $template->getTimeLimit() ?? 3600,
            $template->getPassScore()
        );

        $paper->setGenerationType(PaperGenerationType::TEMPLATE);

        // 按照模板规则选题
        $selectedQuestions = [];

        foreach ($template->getRules() as $rule) {
            $criteria = new SearchCriteria();
            if (null !== $rule->getCategoryId()) {
                $criteria->setCategoryIds([strval($rule->getCategoryId())]);
            }
            if (null !== $rule->getQuestionType()) {
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
     * @param array<int> $categoryIds
     * @param array<string, int> $typeDistribution
     * @param array<string, int> $difficultyDistribution
     */
    public function generateRandom(
        array $categoryIds,
        int $questionCount,
        array $typeDistribution = [],
        array $difficultyDistribution = [],
        int $timeLimit = 3600,
        string $title = '随机试卷',
    ): TestPaper {
        $paper = $this->paperService->createPaper($title, '随机生成的试卷', $timeLimit);
        $paper->setGenerationType(PaperGenerationType::RANDOM);

        $selectedQuestions = $this->selectQuestionsByDistribution(
            $categoryIds,
            $questionCount,
            $typeDistribution,
            $difficultyDistribution
        );

        $selectedQuestions = $this->fillRemainingQuestions($categoryIds, $questionCount, $selectedQuestions);
        $selectedQuestions = array_slice($selectedQuestions, 0, $questionCount);

        $this->paperService->addQuestions($paper, $selectedQuestions);
        $this->paperService->shuffleQuestions($paper);

        return $paper;
    }

    /**
     * @param array<int> $categoryIds
     * @param array<string, int> $typeDistribution
     * @param array<string, int> $difficultyDistribution
     * @return array<array{question: Question, score: int}>
     */
    private function selectQuestionsByDistribution(
        array $categoryIds,
        int $questionCount,
        array $typeDistribution,
        array $difficultyDistribution,
    ): array {
        $typeQuestions = $this->calculateDistribution($questionCount, $typeDistribution);
        $difficultyQuestions = $this->calculateDistribution($questionCount, $difficultyDistribution);
        $selectedQuestions = [];

        foreach ($typeQuestions as $type => $typeCount) {
            foreach ($difficultyQuestions as $diffCount) {
                $count = (int) ceil($typeCount * $diffCount / $questionCount);
                $questions = $this->searchQuestionsWithCriteria($categoryIds, $type, $count);

                foreach ($questions as $question) {
                    $selectedQuestions[] = [
                        'question' => $question,
                        'score' => $this->calculateScore($question),
                    ];
                }
            }
        }

        return $selectedQuestions;
    }

    /**
     * @param array<int> $categoryIds
     * @return array<Question>
     */
    private function searchQuestionsWithCriteria(array $categoryIds, string $type, int $count): array
    {
        $criteria = new SearchCriteria();
        $criteria->setCategoryIds(array_map('strval', $categoryIds));
        $criteria->setTypes([QuestionType::from($type)]);
        $criteria->setLimit($count);

        $result = $this->questionService->searchQuestions($criteria);

        return $result->getItems();
    }

    /**
     * @param array<int> $categoryIds
     * @param array<array{question: Question, score: int}> $selectedQuestions
     * @return array<array{question: Question, score: int}>
     */
    private function fillRemainingQuestions(array $categoryIds, int $questionCount, array $selectedQuestions): array
    {
        if (count($selectedQuestions) >= $questionCount) {
            return $selectedQuestions;
        }

        $remaining = $questionCount - count($selectedQuestions);
        $criteria = new SearchCriteria();
        $criteria->setCategoryIds(array_map('strval', $categoryIds));
        $criteria->setLimit($remaining);

        $result = $this->questionService->searchQuestions($criteria);
        $questions = $result->getItems();

        foreach ($questions as $question) {
            if (!$this->isQuestionAlreadySelected($question, $selectedQuestions)) {
                $selectedQuestions[] = [
                    'question' => $question,
                    'score' => $this->calculateScore($question),
                ];
            }
        }

        return $selectedQuestions;
    }

    /**
     * @param array<array{question: Question, score: int}> $selectedQuestions
     */
    private function isQuestionAlreadySelected(Question $question, array $selectedQuestions): bool
    {
        foreach ($selectedQuestions as $selected) {
            if ($selected['question']->getId() === $question->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * 计算分布
     * @param array<string, int> $distribution
     * @return array<string, int>
     */
    private function calculateDistribution(int $total, array $distribution): array
    {
        if ([] === $distribution) {
            return [];
        }

        $result = [];
        $sum = array_sum($distribution);

        if (0 === $sum) {
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
     * @param array<int> $tags
     */
    public function generateByTags(
        array $tags,
        int $questionCount,
        int $timeLimit = 3600,
        string $title = '专项练习',
    ): TestPaper {
        $paper = $this->paperService->createPaper($title, '基于标签生成的试卷', $timeLimit);
        $paper->setGenerationType(PaperGenerationType::INTELLIGENT);

        $criteria = new SearchCriteria();
        $criteria->setTagIds(array_map('strval', $tags));
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
