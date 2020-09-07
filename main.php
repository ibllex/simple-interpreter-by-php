<?php

const _INTEGER = 'INTEGER';
const _PLUS = 'PLUS';
const _EOF = 'EOF';

class Token
{
    private $type;

    private $value;

    public function __construct($type, $value)
    {
        // token type: INTEGER, PLUS, or EOF
        $this->type = $type;
        // token value: 0, 1, 2. 3, 4, 5, 6, 7, 8, 9, '+', or None
        $this->value = $value;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }


    public function __toString()
    {
        return "Token({$this->type}, {$this->value})";
    }
}

class Interpreter
{
    // client string input, e.g. "3+5"
    private $text;
    // pos is an index into text
    private $pos = 0;
    // current token instance
    private $current_token;

    public function __construct($text)
    {
        $this->text = trim($text);
    }
    
    public function error()
    {
        throw new \Exception('Error parsing input');
    }
    
    /**
     * Lexical analyzer (also know as scanner tokenizer)
     *
     * This method is responsible for breaking sentence
     * apart into tokens. One token one time
     */
    public function get_next_token()
    {
        $text = $this->text;
        
        // is this.pos index pass the end of this.test?
        // if so, then return the EOF token because there is no more
        // input left to convert into token
        if ($this->pos > strlen($text) - 1) {
            return new Token(_EOF, null);
        }
        
        // get a character at the position this.pos and decide
        // what token to create based on the single character
        $current_char = $text[$this->pos];
        
        // if the current character is a digit then convert it into
        // integer, create an INTEGER token, increment this.pos after
        // the integer and return the INTEGER token
        if (is_numeric($current_char)) {
            $token = new Token(_INTEGER, (int) $current_char);
            $this->pos++;
            return $token;
        }
        
        // if the current character is '+' then create a
        // PLUST token, increment this.pos and return PLUS token
        if ($current_char == '+') {
            $token = new Token(_PLUS, $current_char);
            $this->pos++;
            return $token;
        }
        
        $this->error();
    }
    
    /**
     * compare the current token type with the passed token
     * type and if thet match then "eat" the current token,
     * and assign the next token to the this.current_token,
     * otherwise throw an error
     */
    public function eat($token_type)
    {
        if ($this->current_token->type == $token_type) {
            $this->current_token = $this->get_next_token();
        } else {
            $this->error();
        }
    }
    
    /**
     * expr is INTEGER PLUSH INTEGER
     */
    public function expr()
    {
        $this->current_token = $this->get_next_token();
        
        // we expect the current token to be single-digit integer
        $left = $this->current_token;
        $this->eat(_INTEGER);
        
        // we expect the current token to be '+' token
        $op = $this->current_token;
        $this->eat(_PLUS);
        
        // we expect the current token to be single-digit integer
        $right = $this->current_token;
        $this->eat(_INTEGER);
        
        // after the above call the this.current_token is set to EOF token
        
        // at this point INTEGET PLUS INTEGER sequence of tokens has been
        // successfully found and the method can just return the result
        // of adding two integers, thus effectively interpreting client input
        return $left->value + $right->value;
    }
}

while (true) {
    try {
        fwrite(STDOUT, 'calc> ');
        $input = fgets(STDIN);
        $interpreter = new Interpreter($input);
        echo $interpreter->expr() . PHP_EOL;
        unset($interpreter);
    } catch (Exception $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}
