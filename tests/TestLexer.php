<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../inc/Lexer.php';
require_once dirname(__FILE__) . '/../inc/Tokens.php';

class UserControllerTest extends PHPUnit_Framework_TestCase
{
    const SIMPLETEXT = 'This is O\'Reilly\'s book, entitled "Foo <em class="foo">Bars</em> for Baz!"';
    const COMPLEXTEXT = 'O\'Reilly\'s book, <code>Don\'t touch this: "Foo <em class="foo">Bars</em></code> for Baz!"';
    const REPLACETEXT = 'Foo... bar—baz, bar--baz <code>foo--bar...baz—bar!</code> whatever—I don\'t care';
    
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
        $this->assertEquals('This is O&#8217;Reilly&#8217;s book, entitled &#8220;Foo <em class="foo">Bars</em> for Baz!&#8221;', $set->asString());
    }
    
    public function testTranslationComplex()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::COMPLEXTEXT));
        $this->assertEquals('O&#8217;Reilly&#8217;s book, <code>Don\'t touch this: "Foo <em class="foo">Bars</em></code> for Baz!&#8221;', $set->asString());
    }

    public function testTranslationReplace()
    {
        $set = LexEntity\Token\Set::getInstance(new LexEntity\Lexer(self::REPLACETEXT));
        $this->assertEquals('Foo&#8230; bar&#8202;&#8212;&#8202;baz, bar&#8202;&#8212;&#8202;baz <code>foo--bar...baz—bar!</code> whatever&#8202;&#8212;&#8202;I don&#8217;t care', $set->asString());
    }
}