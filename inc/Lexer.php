<?php
namespace Lexentity;

class Lexer
{
    protected $chunks;
    
    /**
     * Constructor
     */
    public function __construct($text)
    {
        $this->chunks = preg_split(
            Token\Token::getRegex(),
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
        );
    }
    
    /**
     * Fetches read-only properties
     */
    public function __get($var)
    {
        switch ($var) {
            case 'chunks':
                return $this->$var;
                break;
            default:
                throw new Exception('Invalid variable');
        }
    }
}
