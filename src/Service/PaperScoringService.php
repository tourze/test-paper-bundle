<?php

namespace Tourze\TestPaperBundle\Service;

use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * 试卷评分服务
 */
class PaperScoringService
{
    public function __construct(
        private readonly PaperQuestionRepository $paperQuestionRepository,
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
            $questionId = $question->getId();

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
     * @param array<string, mixed>|null $customOptions
     */
    private function evaluateAnswer(Question $question, mixed $userAnswer, ?array $customOptions = null): bool
    {
        $correctAnswer = $this->getCorrectAnswer($question, $customOptions);

        switch ($question->getType()) {
            case QuestionType::SINGLE_CHOICE:
            case QuestionType::TRUE_FALSE:
                // correctAnswer is now an array of letters ['A'], userAnswer is a single letter 'A'
                return is_array($correctAnswer) && in_array($userAnswer, $correctAnswer, true);

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
                    return in_array($userAnswer, $correctAnswer, true);
                }

                // 确保两个参数都是字符串类型
                if (!is_string($userAnswer) || !is_string($correctAnswer)) {
                    return false;
                }

                return 0 === strcasecmp(trim($userAnswer), trim($correctAnswer));

            default:
                // 其他题型（简答、论述等）需要人工评分
                return false;
        }
    }

    /**
     * 获取正确答案
     * @param array<string, mixed>|null $customOptions
     */
    private function getCorrectAnswer(Question $question, ?array $customOptions = null): mixed
    {
        // 如果有自定义选项（打乱后的），使用自定义的正确答案
        if (null !== $customOptions && isset($customOptions['correctAnswer'])) {
            return $customOptions['correctAnswer'];
        }

        // 否则使用原始答案
        $apiArray = $question->retrieveApiArray();

        return $apiArray['correctLetters'] ?? [];
    }

    /**
     * 获取详细评分结果
     * @return array{results: array<array{paperQuestion: PaperQuestion, question: Question, userAnswer: mixed, isCorrect: bool, score: int, maxScore: int, isAnswered: bool}>, summary: array{totalScore: int, maxScore: int, correctCount: int, totalCount: int, correctRate: float}}
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
            $questionId = $question->getId();

            $userAnswer = $answers[$questionId] ?? null;
            $isCorrect = false;
            $score = 0;

            if (null !== $userAnswer) {
                $isCorrect = $this->evaluateAnswer($question, $userAnswer, $paperQuestion->getCustomOptions());
                if ($isCorrect) {
                    $score = $paperQuestion->getScore();
                    ++$correctCount;
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
                'isAnswered' => null !== $userAnswer,
            ];
        }

        return [
            'results' => $results,
            'summary' => [
                'totalScore' => $totalScore,
                'maxScore' => $session->getTotalScore() ?? 0,
                'correctCount' => $correctCount,
                'totalCount' => count($paperQuestions),
                'correctRate' => count($paperQuestions) > 0 ? round(($correctCount / count($paperQuestions)) * 100, 2) : 0.0,
            ],
        ];
    }

    /**
     * 按题型统计得分
     * @return array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int, correctRate: float, answerRate: float, scoreRate: float}>
     */
    public function getScoreByType(TestSession $session): array
    {
        $paper = $session->getPaper();
        $answers = $session->getAnswers() ?? [];
        $paperQuestions = $this->paperQuestionRepository->findByPaperWithQuestions($paper);

        $typeStats = [];

        foreach ($paperQuestions as $paperQuestion) {
            $typeStats = $this->processQuestionForTypeStats($paperQuestion, $answers, $typeStats);
        }

        return $this->calculateTypeStatsPercentages($typeStats);
    }

    /**
     * @param array<string, mixed> $answers
     * @param array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}> $typeStats
     * @return array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}>
     */
    private function processQuestionForTypeStats(PaperQuestion $paperQuestion, array $answers, array $typeStats): array
    {
        $question = $paperQuestion->getQuestion();
        $questionType = $question->getType();
        $questionTypeValue = $questionType->value;
        $questionId = $question->getId();

        if (!isset($typeStats[$questionTypeValue])) {
            $typeStats[$questionTypeValue] = $this->initializeTypeStats($questionTypeValue);
        }

        ++$typeStats[$questionTypeValue]['totalQuestions'];
        $typeStats[$questionTypeValue]['maxScore'] += $paperQuestion->getScore();

        if (isset($answers[$questionId])) {
            $typeStats = $this->processAnswerForTypeStats($paperQuestion, $answers[$questionId], $typeStats, $questionTypeValue);
        }

        return $typeStats;
    }

    /**
     * @return array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}
     */
    private function initializeTypeStats(string $questionTypeValue): array
    {
        return [
            'type' => $questionTypeValue,
            'totalQuestions' => 0,
            'answeredQuestions' => 0,
            'correctQuestions' => 0,
            'totalScore' => 0,
            'maxScore' => 0,
        ];
    }

    /**
     * @param array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}> $typeStats
     * @return array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}>
     */
    private function processAnswerForTypeStats(PaperQuestion $paperQuestion, mixed $answer, array $typeStats, string $questionTypeValue): array
    {
        // 确保类型统计存在且结构完整
        if (!isset($typeStats[$questionTypeValue])) {
            $typeStats[$questionTypeValue] = $this->initializeTypeStats($questionTypeValue);
        }

        $stats = $typeStats[$questionTypeValue];
        ++$stats['answeredQuestions'];

        $question = $paperQuestion->getQuestion();
        $isCorrect = $this->evaluateAnswer($question, $answer, $paperQuestion->getCustomOptions());

        if ($isCorrect) {
            ++$stats['correctQuestions'];
            $stats['totalScore'] += $paperQuestion->getScore();
        }

        $typeStats[$questionTypeValue] = $stats;

        return $typeStats;
    }

    /**
     * @param array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int}> $typeStats
     * @return array<string, array{type: string, totalQuestions: int, answeredQuestions: int, correctQuestions: int, totalScore: int, maxScore: int, correctRate: float, answerRate: float, scoreRate: float}>
     */
    private function calculateTypeStatsPercentages(array $typeStats): array
    {
        $result = [];

        foreach ($typeStats as $key => $stats) {
            $result[$key] = $stats + [
                'correctRate' => round(($stats['correctQuestions'] / $stats['totalQuestions']) * 100, 2),
                'answerRate' => round(($stats['answeredQuestions'] / $stats['totalQuestions']) * 100, 2),
                'scoreRate' => $stats['maxScore'] > 0
                    ? round(($stats['totalScore'] / $stats['maxScore']) * 100, 2)
                    : 0.0,
            ];
        }

        return $result;
    }
}
