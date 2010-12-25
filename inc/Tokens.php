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
    protected static $verbatimTags = array(
        'code','pre',
    );

    protected static $numericEntities = array(
        '&amp;' => '&#38;',
        '&lt;' => '&#60;',
        '&gt;' => '&#62;',
        '&hellip;' => '&#8230;',
        '“' => '&#8220;', // cheat
        '”' => '&#8221;', // cheat
        '’' => '&#8217;', // cheat
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
    
    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            return self::ampReplace($this->translated());
        }
    }

    public static function ampReplace($str)
    {
        return str_replace(
            array_keys(self::$numericEntities),
            array_values(self::$numericEntities),
            htmlentities($str, ENT_NOQUOTES, 'utf-8', false)
        );
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
    protected $translatedText = '&#8201;&#8212;&#8201;';
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
    const SPLIT = '/([\'" =])/';

    // these are just named so we can var_dump($this->context) for debugging
    const TYPE_TAG = 'tag';
    const TYPE_ATTRIBUTE = 'attr';
    const TYPE_EQUALS = 'eq';
    const TYPE_VALUE = 'val';
    const TYPE_QUOTE = 'quote';
    const TYPE_SPACE = 'space';
    
    const CONTEXT_BEGIN = 0;
    const CONTEXT_NONE = 1;
    const CONTEXT_ATTRIBUTE = 2;
    
    protected $capture = array();
    
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->pushTagContext(self::tagToText($text));
        $this->calculateAttributes();
    }
    
    protected function calculateAttributes()
    {
        $context = self::CONTEXT_BEGIN;
        $tokens = preg_split(self::SPLIT, trim($this->text, '<>'), -1, PREG_SPLIT_DELIM_CAPTURE);
        $tokens = array_values(array_filter($tokens)); // drop actually empty values
        $attributeQuote = null;
        for ($i=0, $end=count($tokens); $i<$end; $i++) {
            switch ($context) {
                case self::CONTEXT_BEGIN:
                    $this->capture[] = array(
                        'type' => self::TYPE_TAG,
                        'value' => $tokens[$i],
                    );
                    $context = self::CONTEXT_NONE;
                    break;

                case self::CONTEXT_ATTRIBUTE:
                    // $attributeQuote = null;
                    while ($i<$end && $tokens[$i] != $attributeQuote) {
                        $this->capture[] = array(
                            'type' => self::TYPE_VALUE,
                            'value' => $tokens[$i++],
                        );
                    }
                    $this->capture[] = array(
                        'type' => self::TYPE_QUOTE,
                        'value' => $tokens[$i],
                    );
                    $context = self::CONTEXT_NONE;
                    break;

                case self::CONTEXT_NONE:
                    // whitespace is easy
                    if (self::isWhitespace($tokens[$i])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[$i],
                        );
                        continue;
                    }
                    // otherwise must be an attribute name
                    $this->capture[] = array(
                        'type' => self::TYPE_ATTRIBUTE,
                        'value'=> $tokens[$i],
                    );
                    // there might be whitespace after an attribute name; if so capture it
                    if (isset($tokens[$i+1]) && self::isWhitespace($tokens[$i+1])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[++$i],
                        );
                    }
                    // now check for equals. If there's not one, then just continue; attribute has no value.
                    if (!isset($tokens[$i+1]) || (isset($tokens[$i+1]) && '=' != $tokens[$i+1])) {
                        continue;
                    }
                    $this->capture[] = array(
                        'type' => self::TYPE_EQUALS,
                        'value'=> $tokens[++$i],
                    );
                    // there might be whitespace after an equals sign; if so capture it
                    if (self::isWhitespace($tokens[$i+1])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[++$i],
                        );
                    }
                    // next token should be a quote; capture:
                    // note: it's possible that it's not a quote, but let's hope that no one is pretty-entitying really broken HTML
                    // note2: yeah yeah.. wishful thinking
                    $this->capture[] = array(
                        'type' => self::TYPE_QUOTE,
                        'value'=> $tokens[++$i],
                    );
                    $attributeQuote = $tokens[$i];
                    $context = self::CONTEXT_ATTRIBUTE;
                    break;
            }
        }
    }
    
    protected static function isWhitespace($token)
    {
        return preg_match('/^\s+$/', $token);
    }

    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            $str = '<';
            foreach ($this->capture as $token) {
                if (self::TYPE_VALUE == $token['type']) {
                    $str .= self::ampReplace($token['value']);
                } else {
                    $str .= $token['value'];
                }
            }
            return "{$str}>";
        }
    }
}
class CloseTag extends Tag {
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->popTagContext(self::tagToText($text), false);
    }
    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            return $this->translated();
        }
    }
}
class SelfClosingTag extends OpenTag {}

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
    
    public function __toString()
    {
        $ret = '';
        foreach ($this as $token) {
            $ret .= (string)Token::create($token);
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
    
    public function inTagContext($tags)
    {
        if (!is_array($tags)) {
            return in_array($tags, $this->tagContext);
        }
        foreach ($tags as $tag) {
            if (in_array($tag, $this->tagContext)) {
                return true;
            }
        }
        return false;
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
