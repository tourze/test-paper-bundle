<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

class PaperQuestionTest extends TestCase
{
    private PaperQuestion $paperQuestion;

    protected function setUp(): void
    {
        $this->paperQuestion = new PaperQuestion();
    }

    public function testSetAndGetPaper(): void
    {
        $paper = $this->createMock(TestPaper::class);
        $result = $this->paperQuestion->setPaper($paper);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertSame($paper, $this->paperQuestion->getPaper());
    }

    public function testSetAndGetQuestion(): void
    {
        $question = $this->createMock(Question::class);
        $result = $this->paperQuestion->setQuestion($question);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertSame($question, $this->paperQuestion->getQuestion());
    }

    public function testSetAndGetSortOrder(): void
    {
        $sortOrder = 5;
        $result = $this->paperQuestion->setSortOrder($sortOrder);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertEquals($sortOrder, $this->paperQuestion->getSortOrder());
    }

    public function testDefaultSortOrder(): void
    {
        $this->assertEquals(0, $this->paperQuestion->getSortOrder());
    }

    public function testSetAndGetScore(): void
    {
        $score = 10;
        $result = $this->paperQuestion->setScore($score);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertEquals($score, $this->paperQuestion->getScore());
    }

    public function testDefaultScore(): void
    {
        $this->assertEquals(1, $this->paperQuestion->getScore());
    }

    public function testSetAndGetIsRequired(): void
    {
        $result = $this->paperQuestion->setIsRequired(false);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertFalse($this->paperQuestion->isRequired());
    }

    public function testDefaultIsRequired(): void
    {
        $this->assertTrue($this->paperQuestion->isRequired());
    }

    public function testSetAndGetRemark(): void
    {
        $remark = '备注信息';
        $result = $this->paperQuestion->setRemark($remark);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertEquals($remark, $this->paperQuestion->getRemark());
    }

    public function testDefaultRemark(): void
    {
        $this->assertNull($this->paperQuestion->getRemark());
    }

    public function testSetAndGetCustomOptions(): void
    {
        $options = ['option1', 'option2'];
        $result = $this->paperQuestion->setCustomOptions($options);
        
        $this->assertSame($this->paperQuestion, $result);
        $this->assertEquals($options, $this->paperQuestion->getCustomOptions());
    }

    public function testDefaultCustomOptions(): void
    {
        $this->assertNull($this->paperQuestion->getCustomOptions());
    }

    public function testRetrieveApiArray(): void
    {
        $question = $this->createMock(Question::class);
        $question->method('retrieveApiArray')->willReturn(['id' => 'test']);
        
        $this->paperQuestion->setQuestion($question);
        $this->paperQuestion->setSortOrder(1);
        $this->paperQuestion->setScore(5);
        $this->paperQuestion->setRemark('test remark');
        
        $array = $this->paperQuestion->retrieveApiArray();
        $this->assertEquals(1, $array['sortOrder']);
        $this->assertEquals(5, $array['score']);
        $this->assertTrue($array['isRequired']);
        $this->assertEquals('test remark', $array['remark']);
        $this->assertEquals(['id' => 'test'], $array['question']);
    }
}