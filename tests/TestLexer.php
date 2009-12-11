<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../inc/Lexer.php';
require_once dirname(__FILE__) . '/../inc/Tokens.php';

class UserControllerTest extends PHPUnit_Framework_TestCase
{
    const SIMPLETEXT = 'This is O\'Reilly\'s book, entitled "Foo <em class="foo">Bars</em> for Baz!"';
    const COMPLEXTEXT = 'O\'Reilly\'s book, <code>Don\'t touch this: "Foo <em class="foo">Bars</em></code> for Baz!"';
    const REPLACETEXT = 'Foo... bar—baz, bar--baz <code>foo--bar...baz—bar!</code> <pre>foo--bar...baz—bar!</pre> whatever—I don\'t care';
    const URLTEXT = 'Hello <a href="http://example.com/foo=1&bar=2">person</a>.';
    const NONENTITYTEXT = 'I went to the A&P after the A&W where I got free fries with a drink &4 burgers. That made mom & dad &#8220;happy&#8221;.';
    
    public function testSplit()
    {
        $Lexer = new LexEntity\Lexer(self::SIMPLETEXT);
        $this->assertEquals(12, count($Lexer->chunks));
        $this->assertEquals(self::SIMPLETEXT, implode('', $Lexer->chunks));
    }
    
    public function testSet()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::SIMPLETEXT));
        $this->assertTrue($set instanceof Lexentity\Token\Set);
    }
    
    public function testTranslation()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::SIMPLETEXT));
        $this->assertEquals('This is O&#8217;Reilly&#8217;s book, entitled &#8220;Foo <em class="foo">Bars</em> for Baz!&#8221;', (string)$set);
    }
    
    public function testTranslationComplex()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::COMPLEXTEXT));
        $this->assertEquals('O&#8217;Reilly&#8217;s book, <code>Don\'t touch this: "Foo <em class="foo">Bars</em></code> for Baz!&#8221;', (string)$set);
    }

    public function testTranslationReplace()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::REPLACETEXT));
        $this->assertEquals('Foo&#8230; bar&#8201;&#8212;&#8201;baz, bar&#8201;&#8212;&#8201;baz <code>foo--bar...baz—bar!</code> <pre>foo--bar...baz—bar!</pre> whatever&#8201;&#8212;&#8201;I don&#8217;t care', (string)$set);
    }
    
    public function testTranslationURL()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::URLTEXT));
        $this->assertEquals('Hello <a href="http://example.com/foo=1&#38;bar=2">person</a>.', (string)$set);
    }

    public function testTranslationNonEntity()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::NONENTITYTEXT));
        $this->assertEquals('I went to the A&#38;P after the A&#38;W where I got free fries with a drink &#38;4 burgers. That made mom &#38; dad &#8220;happy&#8221;.', (string)$set);
    }
}