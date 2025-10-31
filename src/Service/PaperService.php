<?php

namespace Tourze\TestPaperBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\QuestionBankBundle\Entity\Option;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Exception\PaperException;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * 试卷管理核心服务
 */
#[Autoconfigure(public: true)]
class PaperService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaperQuestionRepository $paperQuestionRepository,
    ) {
    }

    /**
     * 创建空白试卷
     */
    public function createPaper(
        string $title,
        string $description = '',
        int $timeLimit = 3600,
        int $passScore = 60,
    ): TestPaper {
        $paper = new TestPaper();
        $paper->setTitle($title);
        $paper->setDescription($description);
        $paper->setTimeLimit($timeLimit);
        $paper->setPassScore($passScore);
        $paper->setStatus(PaperStatus::DRAFT);
        $paper->setGenerationType(PaperGenerationType::MANUAL);

        $this->entityManager->persist($paper);
        $this->entityManager->flush();

        return $paper;
    }

    /**
     * 添加题目到试卷
     */
    public function addQuestion(
        TestPaper $paper,
        Question $question,
        int $score,
        int $sortOrder = 0,
    ): PaperQuestion {
        // 检查题目是否已存在
        $existing = $this->paperQuestionRepository->findOneBy([
            'paper' => $paper,
            'question' => $question,
        ]);

        if (null !== $existing) {
            throw new PaperException('题目已存在于试卷中');
        }

        $paperQuestion = new PaperQuestion();
        $paperQuestion->setPaper($paper);
        $paperQuestion->setQuestion($question);
        $paperQuestion->setScore($score);
        $paperQuestion->setSortOrder(0 !== $sortOrder ? $sortOrder : $this->getNextSortOrder($paper));

        $paper->addPaperQuestion($paperQuestion);
        $this->updatePaperStatistics($paper);

        $this->entityManager->persist($paperQuestion);
        $this->entityManager->flush();

        return $paperQuestion;
    }

    /**
     * 获取下一个排序号
     */
    private function getNextSortOrder(TestPaper $paper): int
    {
        $maxOrder = 0;
        foreach ($paper->getPaperQuestions() as $pq) {
            if ($pq->getSortOrder() > $maxOrder) {
                $maxOrder = $pq->getSortOrder();
            }
        }

        return $maxOrder + 1;
    }

    /**
     * 更新试卷统计信息
     */
    private function updatePaperStatistics(TestPaper $paper): void
    {
        $totalScore = 0;
        $questionCount = 0;

        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $totalScore += $paperQuestion->getScore();
            ++$questionCount;
        }

        $paper->setTotalScore($totalScore);
        $paper->setQuestionCount($questionCount);
    }

    /**
     * 批量添加题目
     * @param array<array{question: Question, score: int}> $questions
     */
    public function addQuestions(TestPaper $paper, array $questions): void
    {
        $sortOrder = $this->getNextSortOrder($paper);

        foreach ($questions as $questionData) {
            $question = $questionData['question'];
            $score = $questionData['score'];

            $paperQuestion = new PaperQuestion();
            $paperQuestion->setPaper($paper);
            $paperQuestion->setQuestion($question);
            $paperQuestion->setScore($score);
            $paperQuestion->setSortOrder($sortOrder++);

            $paper->addPaperQuestion($paperQuestion);
            $this->entityManager->persist($paperQuestion);
        }

        $this->updatePaperStatistics($paper);
        $this->entityManager->flush();
    }

    /**
     * 移除题目
     */
    public function removeQuestion(TestPaper $paper, PaperQuestion $paperQuestion): void
    {
        if ($paperQuestion->getPaper() !== $paper) {
            throw new PaperException('题目不属于该试卷');
        }

        $paper->removePaperQuestion($paperQuestion);
        $this->entityManager->remove($paperQuestion);

        $this->updatePaperStatistics($paper);
        $this->reorderQuestions($paper);

        $this->entityManager->flush();
    }

    /**
     * 重新排序题目
     */
    private function reorderQuestions(TestPaper $paper): void
    {
        $questions = $paper->getPaperQuestions()->toArray();
        usort($questions, fn ($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());

        $sortOrder = 1;
        foreach ($questions as $paperQuestion) {
            $paperQuestion->setSortOrder($sortOrder++);
        }
    }

    /**
     * 更新题目顺序
     * @param array<string, int> $orderMapping 题目ID到排序号的映射
     */
    public function updateQuestionOrder(TestPaper $paper, array $orderMapping): void
    {
        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $questionId = $paperQuestion->getId();
            if (isset($orderMapping[$questionId])) {
                $paperQuestion->setSortOrder($orderMapping[$questionId]);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * 随机打乱题目顺序
     */
    public function shuffleQuestions(TestPaper $paper): void
    {
        $questions = $paper->getPaperQuestions()->toArray();
        shuffle($questions);

        $sortOrder = 1;
        foreach ($questions as $paperQuestion) {
            $paperQuestion->setSortOrder($sortOrder++);
        }

        $paper->setRandomizeQuestions(true);
        $this->entityManager->flush();
    }

    /**
     * 随机打乱选项顺序
     */
    public function shuffleOptions(TestPaper $paper): void
    {
        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $this->shuffleQuestionOptions($paperQuestion);
        }

        $paper->setRandomizeOptions(true);
        $this->entityManager->flush();
    }

    private function shuffleQuestionOptions(PaperQuestion $paperQuestion): void
    {
        $question = $paperQuestion->getQuestion();

        if (!$this->shouldShuffleOptions($question)) {
            return;
        }

        $optionsArray = [];
        foreach ($question->getOptions() as $key => $option) {
            $optionsArray[strval($key)] = $option;
        }
        $correctAnswers = $this->extractCorrectAnswers($question, $optionsArray);

        $shuffledOptions = $optionsArray;
        shuffle($shuffledOptions);

        $newCorrectAnswers = $this->updateCorrectAnswerIndexes($correctAnswers, $shuffledOptions);

        $paperQuestion->setCustomOptions([
            'options' => $shuffledOptions,
            'correctAnswer' => $newCorrectAnswers,
        ]);
    }

    private function shouldShuffleOptions(Question $question): bool
    {
        if (!in_array($question->getType()->value, ['single_choice', 'multiple_choice'], true)) {
            return false;
        }

        $options = $question->getOptions();

        return !$options->isEmpty();
    }

    /**
     * @param array<int|string, Option> $optionsArray
     * @return array<Option>
     */
    private function extractCorrectAnswers(Question $question, array $optionsArray): array
    {
        $correctAnswers = [];
        $apiArray = $question->retrieveApiArray();
        $correctLetters = $apiArray['correctLetters'] ?? [];

        if (!is_array($correctLetters)) {
            return $correctAnswers;
        }

        foreach ($correctLetters as $letter) {
            if (is_string($letter) && isset($optionsArray[$letter])) {
                $correctAnswers[] = $optionsArray[$letter];
            }
        }

        return $correctAnswers;
    }

    /**
     * @param array<Option> $correctAnswers
     * @param list<Option> $shuffledOptions
     * @return array<int>
     */
    private function updateCorrectAnswerIndexes(array $correctAnswers, array $shuffledOptions): array
    {
        $newCorrectAnswers = [];

        foreach ($correctAnswers as $correctAnswer) {
            $newIndex = array_search($correctAnswer, $shuffledOptions, true);
            if (false !== $newIndex) {
                $newCorrectAnswers[] = $newIndex;
            }
        }

        return $newCorrectAnswers;
    }

    /**
     * 发布试卷
     */
    public function publishPaper(TestPaper $paper): void
    {
        if (PaperStatus::DRAFT !== $paper->getStatus()) {
            throw new PaperException('只有草稿状态的试卷可以发布');
        }

        if ($paper->getQuestionCount() <= 0) {
            throw new PaperException('试卷中没有题目');
        }

        $paper->setStatus(PaperStatus::PUBLISHED);
        $this->entityManager->flush();
    }

    /**
     * 归档试卷
     */
    public function archivePaper(TestPaper $paper): void
    {
        if (PaperStatus::PUBLISHED !== $paper->getStatus()) {
            throw new PaperException('只有已发布的试卷可以归档');
        }

        $paper->setStatus(PaperStatus::ARCHIVED);
        $this->entityManager->flush();
    }

    /**
     * 复制试卷
     */
    public function duplicatePaper(TestPaper $originalPaper, string $newTitle): TestPaper
    {
        $newPaper = new TestPaper();
        $newPaper->setTitle($newTitle);
        $newPaper->setDescription($originalPaper->getDescription());
        $newPaper->setTimeLimit($originalPaper->getTimeLimit());
        $newPaper->setTotalScore($originalPaper->getTotalScore());
        $newPaper->setPassScore($originalPaper->getPassScore());
        $newPaper->setQuestionCount($originalPaper->getQuestionCount());
        $newPaper->setGenerationType($originalPaper->getGenerationType());
        $newPaper->setStatus(PaperStatus::DRAFT);

        // 复制试卷设置
        $newPaper->setAllowRetake($originalPaper->isAllowRetake());
        $newPaper->setMaxAttempts($originalPaper->getMaxAttempts());

        $this->entityManager->persist($newPaper);

        // 复制题目
        foreach ($originalPaper->getPaperQuestions() as $originalPQ) {
            $newPQ = new PaperQuestion();
            $newPQ->setPaper($newPaper);
            $newPQ->setQuestion($originalPQ->getQuestion());
            $newPQ->setSortOrder($originalPQ->getSortOrder());
            $newPQ->setScore($originalPQ->getScore());
            $newPQ->setIsRequired($originalPQ->isRequired());
            $newPQ->setRemark($originalPQ->getRemark());

            $newPaper->addPaperQuestion($newPQ);
            $this->entityManager->persist($newPQ);
        }

        $this->entityManager->flush();

        return $newPaper;
    }
}
