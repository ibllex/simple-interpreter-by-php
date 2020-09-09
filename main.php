#!/usr/bin/env php

<?php

// SIP is abbreviation of 'simple interpreter by php' or just 'simple'

/**
 * LEXER
 */

// Token types
const SIP_INTEGER = 'INTEGER';
const SIP_PLUS = 'PLUS';
const SIP_MINUS = 'MINUS';
const SIP_MUL = 'MUL';
const SIP_DIV = 'DIV';
const SIP_LPAREN = '(';
const SIP_RPAREN = ')';
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

class Lexer
{
    // client string input, e.g. "3+5", "12 - 8", "12 + 19", etc
    private $text;
    // pos is an index into text
    private $pos = 0;
    // current char
    private $current_char;

    public function __construct($text)
    {
        $this->text = trim($text);
        $this->current_char = $this->text[$this->pos];
    }
    
    public function error()
    {
        throw new \Exception('Invalid character');
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
            
            if ($this->current_char == '+') {
                $this->advance();
                return new Token(SIP_PLUS, '+');
            }

            if ($this->current_char == '-') {
                $this->advance();
                return new Token(SIP_MINUS, '-');
            }

            if ($this->current_char == '*') {
                $this->advance();
                return new Token(SIP_MUL, '*');
            }

            if ($this->current_char == '/') {
                $this->advance();
                return new Token(SIP_DIV, '/');
            }

            if ($this->current_char == '(') {
                $this->advance();
                return new Token(SIP_LPAREN, '(');
            }

            if ($this->current_char == ')') {
                $this->advance();
                return new Token(SIP_RPAREN, ')');
            }

            $this->error();
        }

        return new Token(SIP_EOF, null);
    }
}

/**
 * PARSER
 */

class AST
{
    //
}

class BinOp extends AST
{
    private $left;

    private $token;
    
    private $op;

    private $right;

    public function __construct(AST $left, Token $op, AST $right)
    {
        $this->left = $left;
        $this->token = $op;
        $this->op = $op;
        $this->right = $right;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

class UnaryOp extends AST
{
    private $op;
    
    private $right;

    public function __construct(Token $op, AST $right)
    {
        $this->op = $op;
        $this->right = $right;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

class Num extends AST
{
    private $token;

    private $value;

    public function __construct(Token $token)
    {
        $this->token = $token;
        $this->value = $token->value;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

class Parser
{
    private $lexer;

    private $current_token;

    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
        // set current token to the first token taken from the input
        $this->current_token = $lexer->get_next_token();
    }
    
    public function error()
    {
        throw new \Exception('Invalid syntax');
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
            $this->current_token = $this->lexer->get_next_token();
        } else {
            $this->error();
        }
    }
    
    /**
     * factor: (PLUS|MINUS) factor | INTEGER | LPAREN expr RPAREN
     */
    public function factor()
    {
        $token = $this->current_token;
        if (in_array($token->type, [SIP_PLUS, SIP_MINUS])) {
            $this->eat($token->type);
            return new UnaryOp($token, $this->factor());
        } elseif ($token->type == SIP_INTEGER) {
            $this->eat(SIP_INTEGER);
            return new Num($token);
        } elseif ($token->type == SIP_LPAREN) {
            $this->eat(SIP_LPAREN);
            $node = $this->expr();
            $this->eat(SIP_RPAREN);
            return $node;
        }
    }

    /**
     * term     : factor ((MUL/DIV) factor)*
     * factor   : factor: (PLUS|MINUS) factor | INTEGER | LPAREN expr RPAREN
     */
    public function term()
    {
        $node = $this->factor();

        while (in_array($this->current_token->type, [SIP_MUL, SIP_DIV])) {
            $token = $this->current_token;
            if ($token->type == SIP_MUL) {
                $this->eat(SIP_MUL);
            } elseif ($token->type == SIP_DIV) {
                $this->eat(SIP_DIV);
            }
            
            $node = new BinOp($node, $token, $this->factor());
        }
        
        return $node;
    }

    /**
     * arithmetic expression parser / interpreter
     * expr     : term ((PLUS/MINUS) term)*
     * term     : factor ((MUL/DIV) factor)*
     * factor   : factor: (PLUS|MINUS) factor | INTEGER | LPAREN expr RPAREN
     */
    public function expr()
    {
        $node = $this->term();

        while (in_array($this->current_token->type, [SIP_PLUS, SIP_MINUS])) {
            $token = $this->current_token;
            if ($token->type == SIP_PLUS) {
                $this->eat(SIP_PLUS);
            } elseif ($token->type == SIP_MINUS) {
                $this->eat(SIP_MINUS);
            }
            
            $node = new BinOp($node, $token, $this->term());
        }

        return $node;
    }
    
    public function parse()
    {
        return $this->expr();
    }
}

/**
 * INTERPRETER
 */

class NodeVisitor
{
    public function visit(AST $node)
    {
        $class_name = preg_replace('/\s+/u', '', ucwords(get_class($node)));
        $method_name = 'visit_' . strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $class_name));
        
        if (! method_exists($this, $method_name)) {
            $this->generic_visit($method_name);
        }
        
        return $this->{$method_name}($node);
    }
    
    private function generic_visit($method_name)
    {
        throw new \Exception('No ' . $method_name . ' method.');
    }
}

class Interpreter extends NodeVisitor
{
    private $parser;
    
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }
    
    public function visit_bin_op(BinOp $node)
    {
        if ($node->op->type == SIP_PLUS) {
            return $this->visit($node->left) + $this->visit($node->right);
        } elseif ($node->op->type == SIP_MINUS) {
            return $this->visit($node->left) - $this->visit($node->right);
        } elseif ($node->op->type == SIP_MUL) {
            return $this->visit($node->left) * $this->visit($node->right);
        } elseif ($node->op->type == SIP_DIV) {
            return $this->visit($node->left) / $this->visit($node->right);
        }
    }
    
    public function visit_unary_op(UnaryOp $op)
    {
        if ($op->op->type == SIP_MINUS) {
            return - $this->visit($op->right);
        }
        
        return $this->visit($op->right);
    }

    public function visit_num(Num $node)
    {
        return $node->value;
    }
    
    public function interpret()
    {
        $tree = $this->parser->parse();
        return $this->visit($tree);
    }
}

while (true) {
    try {
        fwrite(STDOUT, 'calc> ');
        $input = fgets(STDIN);
        $lexer = new Lexer($input);
        $parser = new Parser($lexer);
        $interpreter = new Interpreter($parser);
        echo $interpreter->interpret() . PHP_EOL;
        unset($interpreter);
    } catch (Exception $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}
