<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;

class TestPaperTest extends TestCase
{
    private TestPaper $testPaper;

    protected function setUp(): void
    {
        $this->testPaper = new TestPaper();
    }

    public function testSetAndGetTitle(): void
    {
        $title = '期末考试';
        $result = $this->testPaper->setTitle($title);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($title, $this->testPaper->getTitle());
    }

    public function testSetAndGetDescription(): void
    {
        $description = '2024年春季期末考试';
        $result = $this->testPaper->setDescription($description);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($description, $this->testPaper->getDescription());
    }

    public function testDefaultDescription(): void
    {
        $this->assertNull($this->testPaper->getDescription());
    }

    public function testSetAndGetStatus(): void
    {
        $status = PaperStatus::PUBLISHED;
        $result = $this->testPaper->setStatus($status);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($status, $this->testPaper->getStatus());
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals(PaperStatus::DRAFT, $this->testPaper->getStatus());
    }

    public function testSetAndGetGenerationType(): void
    {
        $type = PaperGenerationType::TEMPLATE;
        $result = $this->testPaper->setGenerationType($type);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($type, $this->testPaper->getGenerationType());
    }

    public function testDefaultGenerationType(): void
    {
        $this->assertEquals(PaperGenerationType::MANUAL, $this->testPaper->getGenerationType());
    }

    public function testSetAndGetTotalScore(): void
    {
        $score = 120;
        $result = $this->testPaper->setTotalScore($score);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($score, $this->testPaper->getTotalScore());
    }

    public function testDefaultTotalScore(): void
    {
        $this->assertEquals(100, $this->testPaper->getTotalScore());
    }

    public function testSetAndGetPassScore(): void
    {
        $score = 72;
        $result = $this->testPaper->setPassScore($score);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($score, $this->testPaper->getPassScore());
    }

    public function testDefaultPassScore(): void
    {
        $this->assertEquals(60, $this->testPaper->getPassScore());
    }

    public function testSetAndGetTimeLimit(): void
    {
        $timeLimit = 7200;
        $result = $this->testPaper->setTimeLimit($timeLimit);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($timeLimit, $this->testPaper->getTimeLimit());
    }

    public function testDefaultTimeLimit(): void
    {
        $this->assertNull($this->testPaper->getTimeLimit());
    }

    public function testSetAndGetQuestionCount(): void
    {
        $count = 25;
        $result = $this->testPaper->setQuestionCount($count);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($count, $this->testPaper->getQuestionCount());
    }

    public function testDefaultQuestionCount(): void
    {
        $this->assertEquals(0, $this->testPaper->getQuestionCount());
    }

    public function testSetAndGetAllowRetake(): void
    {
        $result = $this->testPaper->setAllowRetake(true);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertTrue($this->testPaper->isAllowRetake());
    }

    public function testDefaultAllowRetake(): void
    {
        $this->assertTrue($this->testPaper->isAllowRetake());
    }

    public function testSetAndGetMaxAttempts(): void
    {
        $attempts = 3;
        $result = $this->testPaper->setMaxAttempts($attempts);
        
        $this->assertSame($this->testPaper, $result);
        $this->assertEquals($attempts, $this->testPaper->getMaxAttempts());
    }

    public function testDefaultMaxAttempts(): void
    {
        $this->assertNull($this->testPaper->getMaxAttempts());
    }

    public function testGetPaperQuestions(): void
    {
        $collection = $this->testPaper->getPaperQuestions();
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $collection);
        $this->assertTrue($collection->isEmpty());
    }
}