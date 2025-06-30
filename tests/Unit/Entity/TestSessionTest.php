<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

class TestSessionTest extends TestCase
{
    private TestSession $session;

    protected function setUp(): void
    {
        $this->session = new TestSession();
    }

    public function testSetAndGetPaper(): void
    {
        $paper = $this->createMock(TestPaper::class);
        $result = $this->session->setPaper($paper);
        
        $this->assertSame($this->session, $result);
        $this->assertSame($paper, $this->session->getPaper());
    }

    public function testSetAndGetUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $result = $this->session->setUser($user);
        
        $this->assertSame($this->session, $result);
        $this->assertSame($user, $this->session->getUser());
    }

    public function testSetAndGetStatus(): void
    {
        $status = SessionStatus::IN_PROGRESS;
        $result = $this->session->setStatus($status);
        
        $this->assertSame($this->session, $result);
        $this->assertEquals($status, $this->session->getStatus());
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals(SessionStatus::PENDING, $this->session->getStatus());
    }

    public function testSetAndGetScore(): void
    {
        $score = 85;
        $result = $this->session->setScore($score);
        
        $this->assertSame($this->session, $result);
        $this->assertEquals($score, $this->session->getScore());
    }

    public function testDefaultScore(): void
    {
        $this->assertEquals(0, $this->session->getScore());
    }


    public function testSetAndGetStartTime(): void
    {
        $startTime = new \DateTimeImmutable();
        $result = $this->session->setStartTime($startTime);
        
        $this->assertSame($this->session, $result);
        $this->assertEquals($startTime, $this->session->getStartTime());
    }

    public function testSetAndGetEndTime(): void
    {
        $endTime = new \DateTimeImmutable();
        $result = $this->session->setEndTime($endTime);
        
        $this->assertSame($this->session, $result);
        $this->assertEquals($endTime, $this->session->getEndTime());
    }
}