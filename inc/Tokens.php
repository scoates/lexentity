<?php
namespace Lexentity\Token;

abstract class Token
{
    protected $text;
    protected $translatedText = null;
    protected static $types = array(
        'Apostrophe' => "'",
        'Ellipsis' => '\.{3,}',
        'Quote' => '"',
        'Tag' => '<.*?>',
        'Emdash' => '—|--',
    );
    
    protected function __construct($text)
    {
        $this->text = $text;
    }

    public static function getRegex()
    {
        $regex = '/';
        $counter = count(self::$types);
        foreach (self::$types as $type) {
            $regex .= '(' . $type .')';
            if (--$counter) {
                $regex .= '|';
            }
        }
        $regex .= '/';
        return $regex;
    }
    
    public static function create($text)
    {
        // this is slow, but effective
        foreach (self::$types as $type => $regex) {
            if (preg_match('/^' . $regex . '$/', $text)) {
                $class = __NAMESPACE__ . '\\' . $type;
                return $class::createSubType($text);
            }
        }
        return Plaintext::createSubType($text);
    }
    
    protected static function createSubType($text)
    {
        return new static($text); // ftw!
    }
    
    public function translated()
    {
        return $this->translatedText ? $this->translatedText : $this->text;
    }
    
    public function asString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext('code')) {
            return $this->text;
        } else {
            return $this->translated();
        }
    }
    
}

class Plaintext extends Token {}

class Apostrophe extends Token
{
    protected $translatedText = '&#8217;';
}
class Ellipsis extends Token
{
    protected $translatedText = '&#8230;';
}
class Emdash extends Token
{
    protected $translatedText = '&#8212;';
}
class Quote extends Token
{
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        if ($set->withinQuotation) {
            $set->withinQuotation = false;
            $this->translatedText = '&#8221;';
        } else {
            $set->withinQuotation = true;
            $this->translatedText = '&#8220;';
        }
    }
}
abstract class Tag extends Token
{
    protected static function createSubType($text)
    {
        if (substr($text, 0, 2) == '</') {
            return new CloseTag($text);
        } else if (substr($text, -2) == '/>') {
            return new SelfClosingTag($text);
        } else {
            return new OpenTag($text);
        }
    }
    
    protected static function tagToText($tag)
    {
        // cheat!
        return preg_replace('!</?(\w+).*!', '\1', $tag);
    }
}
class OpenTag extends Tag
{
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->pushTagContext(self::tagToText($text));
    }
}
class CloseTag extends Tag {
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->popTagContext(self::tagToText($text), false);
    }
}
class SelfClosingTag extends Tag {}

class Set implements \Iterator
{
    protected static $instance = null;

    protected $lexer;
    protected $index = 0;
    protected $tagContext = array();
    public $withinQuotation = false;
    
    public static function getInstance(\LexEntity\Lexer $lexer = null)
    {
        if ($lexer || !self::$instance) {
            self::$instance = new self($lexer);
        }
        return self::$instance;
    }
    
    protected function __construct(\LexEntity\Lexer $lexer)
    {
        $this->Lexer = $lexer;
    }
    
    public function asString()
    {
        $ret = '';
        foreach ($this as $token) {
            $ret .= Token::create($token)->asString();
        }
        return $ret;
    }
    
    public function pushTagContext($tag)
    {
        array_push($this->tagContext, $tag);
    }
    
    /**
     * Pop
     *
     * @param string $tag     tag name
     * @param Bool   $strict  throw an exception if the current context is not the same as $tag
     * @param Bool   $unravel continue popping the tag stack until the proper context is found
     */
    public function popTagContext($tag, $strict = true, $unravel = false)
    {
        $poppedTag = array_pop($this->tagContext);
        if ($strict && $tag != $poppedTag) {
            throw new Exception('closing tag is not last opened tag');
        }
        if ($unravel) {
            while ($tag != $poppedTag && count($this->tagContext)) {
                $poppedTag = array_pop($this->tagContext);
            }
        } else if ($tag != $poppedTag) {
            // push it back on
            array_push($this->tagContext, $poppedTag);
        }
    }
    
    public function inTagContext($tag)
    {
        return in_array($tag, $this->tagContext);
    }
    
    //// ITERATOR:
    
    public function current()
    {
        return $this->Lexer->chunks[$this->index];
    }
    public function next()
    {
        ++$this->index;
    }
    public function key()
    {
        return $this->index;
    }
    public function valid()
    {
        return $this->index >= 0 && $this->index < count($this->Lexer->chunks);
    }
    public function rewind()
    {
        $this->tagContext = array();
        $this->inQuote = false;
        return $this->index = 0;
    }
}