<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

class PaperTemplateTest extends TestCase
{
    private PaperTemplate $template;

    protected function setUp(): void
    {
        $this->template = new PaperTemplate();
    }

    public function testSetAndGetName(): void
    {
        $name = '模板名称';
        $result = $this->template->setName($name);
        
        $this->assertSame($this->template, $result);
        $this->assertEquals($name, $this->template->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $description = '模板描述';
        $result = $this->template->setDescription($description);
        
        $this->assertSame($this->template, $result);
        $this->assertEquals($description, $this->template->getDescription());
    }

    public function testDefaultValues(): void
    {
        $this->assertEquals(0, $this->template->getTotalQuestions());
        $this->assertEquals(100, $this->template->getTotalScore());
        $this->assertEquals(60, $this->template->getPassScore());
        $this->assertTrue($this->template->isActive());
        $this->assertFalse($this->template->isShuffleQuestions());
        $this->assertFalse($this->template->isShuffleOptions());
    }

    public function testRetrieveApiArray(): void
    {
        $this->template->setName('Test Template');
        $this->template->setTotalQuestions(10);
        
        $array = $this->template->retrieveApiArray();
        $this->assertEquals('Test Template', $array['name']);
        $this->assertEquals(10, $array['totalQuestions']);
        $this->assertEquals(0, $array['ruleCount']);
    }
}