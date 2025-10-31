<?php

namespace Tourze\TestPaperBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

/**
 * @internal
 */
#[CoversClass(TestSession::class)]
final class TestSessionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $session = new TestSession();

        // 设置必需的 paper 关联
        $paper = new TestPaper();
        $paper->setTitle('测试试卷 - TestSession - ' . uniqid());
        $session->setPaper($paper);

        // 设置必需的 user 关联
        $user = new InMemoryUser('test-user-' . uniqid(), 'password');
        $session->setUser($user);

        return $session;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield ['status', SessionStatus::COMPLETED];
        yield ['score', 95];
        yield ['totalScore', 100];
        yield ['startTime', new \DateTimeImmutable('2024-01-01 10:00:00')];
        yield ['endTime', new \DateTimeImmutable('2024-01-01 11:30:00')];
        yield ['answers', ['q1' => 'A', 'q2' => ['B', 'C']]];
        yield ['passed', true];
        yield ['attemptNumber', 2];
        yield ['duration', 5400];
        yield ['remark', '测试备注'];
    }
}
