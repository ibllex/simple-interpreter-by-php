#!/usr/bin/env php

<?php

// SIP is abbreviation of 'simple interpreter by php' or just 'simple'
const SIP_INTEGER = 'INTEGER';
const SIP_PLUS = 'PLUS';
const SIP_MINUS = 'MINUS';
const SIP_EOF = 'EOF';
const SIP_WHITESPACE = ' ';

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
    // client string input, e.g. "3+5", "12 - 8", "12 + 19", etc
    private $text;
    // pos is an index into text
    private $pos = 0;
    // current token instance
    private $current_token;
    // current char
    private $current_char;

    public function __construct($text)
    {
        $this->text = trim($text);
        $this->current_char = $this->text[$this->pos];
    }
    
    public function error()
    {
        throw new \Exception('Error parsing input');
    }

    /**
     * advance the 'pos' pointer and set the current_char variable
     */
    public function advance()
    {
        $this->pos++;
        if ($this->pos > strlen($this->text) - 1) {
            // indicates the end of input
            $this->current_char = null;
        } else {
            $this->current_char = $this->text[$this->pos];
        }
    }

    public function skip_whitespace()
    {
        while ($this->current_char != null && $this->current_char == SIP_WHITESPACE) {
            $this->advance();
        }
    }

    /**
     * return a (multidigit) integer consumed from the input
     */
    public function integer()
    {
        $result = '';
        while ($this->current_char != null && is_numeric($this->current_char)) {
            $result .= $this->current_char;
            $this->advance();
        }

        return (int) $result;
    }
    
    /**
     * Lexical analyzer (also know as scanner tokenizer)
     *
     * This method is responsible for breaking sentence
     * apart into tokens. One token one time
     */
    public function get_next_token()
    {
        while ($this->current_char != null) {
            // if the current character is a whitespace then skip
            // consecutive whitespaces
            if ($this->current_char == SIP_WHITESPACE) {
                $this->skip_whitespace();
                continue;
            }

            // if the current character is a digit then get multidigit
            // consumed from the input, convert it into an INTEGER token
            // and return the INTEGER token
            if (is_numeric($this->current_char)) {
                return new Token(SIP_INTEGER, $this->integer());
            }
            
            // if the current character is '+' then create a
            // PLUST token, increment this.pos and return PLUS token
            if ($this->current_char == '+') {
                $this->advance();
                return new Token(SIP_PLUS, '+');
            }

            // if the current character is '-' then create a
            // MINUS token, increment this.pos and return MINUS token
            if ($this->current_char == '-') {
                $this->advance();
                return new Token(SIP_MINUS, '-');
            }

            $this->error();
        }

        return new Token(SIP_EOF, null);
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
     * expr is INTEGER MINUS INTEGER
     */
    public function expr()
    {
        $this->current_token = $this->get_next_token();
        
        // we expect the current token to be an integer
        $left = $this->current_token;
        $this->eat(SIP_INTEGER);
        
        // we expect the current token to be either a '+' or '-' token
        $op = $this->current_token;
        if ($op->type == SIP_PLUS) {
            $this->eat(SIP_PLUS);
        } else {
            $this->eat(SIP_MINUS);
        }
        
        // we expect the current token to be an integer
        $right = $this->current_token;
        $this->eat(SIP_INTEGER);
        
        // after the above call the this.current_token is set to EOF token
        
        // at this point INTEGET PLUS INTEGER sequence of tokens has been
        // successfully found and the method can just return the result
        // of adding or subtracting two integers,
        // thus effectively interpreting client input
        if ($op->type == SIP_PLUS) {
            return $left->value + $right->value;
        }
        
        return $left->value - $right->value;
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
