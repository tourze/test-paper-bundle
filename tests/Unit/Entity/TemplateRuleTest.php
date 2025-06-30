<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

class TemplateRuleTest extends TestCase
{
    private TemplateRule $rule;

    protected function setUp(): void
    {
        $this->rule = new TemplateRule();
    }

    public function testSetAndGetTemplate(): void
    {
        $template = $this->createMock(PaperTemplate::class);
        $result = $this->rule->setTemplate($template);
        
        $this->assertSame($this->rule, $result);
        $this->assertSame($template, $this->rule->getTemplate());
    }

    public function testSetAndGetQuestionCount(): void
    {
        $count = 5;
        $result = $this->rule->setQuestionCount($count);
        
        $this->assertSame($this->rule, $result);
        $this->assertEquals($count, $this->rule->getQuestionCount());
    }

    public function testDefaultValues(): void
    {
        $this->assertEquals(1, $this->rule->getQuestionCount());
        $this->assertEquals(1, $this->rule->getScorePerQuestion());
        $this->assertEquals(0, $this->rule->getSort());
        $this->assertFalse($this->rule->isExcludeUsed());
    }

    public function testGetTotalScore(): void
    {
        $this->rule->setQuestionCount(5);
        $this->rule->setScorePerQuestion(10);
        
        $this->assertEquals(50, $this->rule->getTotalScore());
    }

    public function testRetrieveApiArray(): void
    {
        $this->rule->setQuestionCount(3);
        $this->rule->setScorePerQuestion(5);
        
        $array = $this->rule->retrieveApiArray();
        $this->assertEquals(3, $array['questionCount']);
        $this->assertEquals(5, $array['scorePerQuestion']);
        $this->assertEquals(15, $array['totalScore']);
    }
}