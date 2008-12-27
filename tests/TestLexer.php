<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../inc/Lexer.php';
require_once dirname(__FILE__) . '/../inc/Tokens.php';

class UserControllerTest extends PHPUnit_Framework_TestCase
{
    const SIMPLETEXT = 'This is O\'Reilly\'s book, entitled "Foo <em class="foo">Bars</em> for Baz!"';
    const COMPLEXTEXT = 'O\'Reilly\'s book, <code>Don\'t touch this: "Foo <em class="foo">Bars</em></code> for Baz!"';
    
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
}