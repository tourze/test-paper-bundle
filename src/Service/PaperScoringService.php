<?php

namespace Tourze\TestPaperBundle\Service;

use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * 试卷评分服务
 */
class PaperScoringService
{
    public function __construct(
        private readonly PaperQuestionRepository $paperQuestionRepository
    ) {
    }

    /**
     * 计算总分
     */
    public function calculateScore(TestSession $session): int
    {
        $paper = $session->getPaper();
        $answers = $session->getAnswers() ?? [];
        $paperQuestions = $this->paperQuestionRepository->findByPaperWithQuestions($paper);
        
        $totalScore = 0;
        
        foreach ($paperQuestions as $paperQuestion) {
            $question = $paperQuestion->getQuestion();
            $questionId = $question->getId()->__toString();
            
            if (!isset($answers[$questionId])) {
                continue; // 未答题，得分为0
            }
            
            $userAnswer = $answers[$questionId];
            $isCorrect = $this->evaluateAnswer($question, $userAnswer, $paperQuestion->getCustomOptions());
            
            if ($isCorrect) {
                $totalScore += $paperQuestion->getScore();
            }
        }
        
        return $totalScore;
    }

    /**
     * 评估答案是否正确
     */
    private function evaluateAnswer($question, $userAnswer, ?array $customOptions = null): bool
    {
        $correctAnswer = $this->getCorrectAnswer($question, $customOptions);

        switch ($question->getType()) {
            case QuestionType::SINGLE_CHOICE:
            case QuestionType::TRUE_FALSE:
                // correctAnswer is now an array of letters ['A'], userAnswer is a single letter 'A'
                return is_array($correctAnswer) && in_array($userAnswer, $correctAnswer);

            case QuestionType::MULTIPLE_CHOICE:
                // Both should be arrays of letters
                if (!is_array($userAnswer) || !is_array($correctAnswer)) {
                    return false;
                }
                sort($userAnswer);
                sort($correctAnswer);
                return $userAnswer === $correctAnswer;

            case QuestionType::FILL_BLANK:
                // 填空题可能有多个答案
                if (is_array($correctAnswer)) {
                    return in_array($userAnswer, $correctAnswer);
                }
                return strcasecmp(trim($userAnswer), trim($correctAnswer)) === 0;

            default:
                // 其他题型（简答、论述等）需要人工评分
                return false;
        }
    }

    /**
     * 获取正确答案
     */
    private function getCorrectAnswer($question, ?array $customOptions = null): mixed
    {
        // 如果有自定义选项（打乱后的），使用自定义的正确答案
        if ($customOptions && isset($customOptions['correctAnswer'])) {
            return $customOptions['correctAnswer'];
        }

        // 否则使用原始答案 - 兼容 exam-bundle 的方法
        $apiArray = $question->retrieveApiArray();
        return $apiArray['correctLetters'] ?? [];
    }

    /**
     * 获取详细评分结果
     */
    public function getDetailedResults(TestSession $session): array
    {
        $paper = $session->getPaper();
        $answers = $session->getAnswers() ?? [];
        $paperQuestions = $this->paperQuestionRepository->findByPaperWithQuestions($paper);

        $results = [];
        $totalScore = 0;
        $correctCount = 0;

        foreach ($paperQuestions as $paperQuestion) {
            $question = $paperQuestion->getQuestion();
            $questionId = $question->getId()->__toString();

            $userAnswer = $answers[$questionId] ?? null;
            $isCorrect = false;
            $score = 0;

            if ($userAnswer !== null) {
                $isCorrect = $this->evaluateAnswer($question, $userAnswer, $paperQuestion->getCustomOptions());
                if ($isCorrect) {
                    $score = $paperQuestion->getScore();
                    $correctCount++;
                }
            }

            $totalScore += $score;

            $results[] = [
                'paperQuestion' => $paperQuestion,
                'question' => $question,
                'userAnswer' => $userAnswer,
                'isCorrect' => $isCorrect,
                'score' => $score,
                'maxScore' => $paperQuestion->getScore(),
                'isAnswered' => $userAnswer !== null,
            ];
        }

        return [
            'results' => $results,
            'summary' => [
                'totalScore' => $totalScore,
                'maxScore' => $session->getTotalScore(),
                'correctCount' => $correctCount,
                'totalCount' => count($paperQuestions),
                'correctRate' => count($paperQuestions) > 0 ? round(($correctCount / count($paperQuestions)) * 100, 2) : 0,
            ]
        ];
    }

    /**
     * 按题型统计得分
     */
    public function getScoreByType(TestSession $session): array
    {
        $paper = $session->getPaper();
        $answers = $session->getAnswers() ?? [];
        $paperQuestions = $this->paperQuestionRepository->findByPaperWithQuestions($paper);

        $typeStats = [];

        foreach ($paperQuestions as $paperQuestion) {
            $question = $paperQuestion->getQuestion();
            $questionType = $question->getType();
            $questionTypeValue = $questionType->value;
            $questionId = $question->getId()->__toString();

            if (!isset($typeStats[$questionTypeValue])) {
                $typeStats[$questionTypeValue] = [
                    'type' => $questionTypeValue,
                    'totalQuestions' => 0,
                    'answeredQuestions' => 0,
                    'correctQuestions' => 0,
                    'totalScore' => 0,
                    'maxScore' => 0,
                ];
            }

            $typeStats[$questionTypeValue]['totalQuestions']++;
            $typeStats[$questionTypeValue]['maxScore'] += $paperQuestion->getScore();

            if (isset($answers[$questionId])) {
                $typeStats[$questionTypeValue]['answeredQuestions']++;

                $isCorrect = $this->evaluateAnswer($question, $answers[$questionId], $paperQuestion->getCustomOptions());
                if ($isCorrect) {
                    $typeStats[$questionTypeValue]['correctQuestions']++;
                    $typeStats[$questionTypeValue]['totalScore'] += $paperQuestion->getScore();
                }
            }
        }

        // 计算百分比
        foreach ($typeStats as &$stats) {
            $stats['correctRate'] = $stats['totalQuestions'] > 0
                ? round(($stats['correctQuestions'] / $stats['totalQuestions']) * 100, 2)
                : 0;
            $stats['answerRate'] = $stats['totalQuestions'] > 0
                ? round(($stats['answeredQuestions'] / $stats['totalQuestions']) * 100, 2)
                : 0;
            $stats['scoreRate'] = $stats['maxScore'] > 0
                ? round(($stats['totalScore'] / $stats['maxScore']) * 100, 2)
                : 0;
        }

        return $typeStats;
    }
}