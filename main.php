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
const SIP_BEGIN = 'BEGIN';
const SIP_END = 'END';
const SIP_DOT = 'DOT';
const SIP_ASSIGN = 'ASSIGN';
const SIP_SEMI = 'SEMI';
const SIP_ID = 'ID';
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
    // keywords
    private $reserved_keywords = [];

    public function __construct($text)
    {
        $this->text = trim($text);
        $this->current_char = $this->text[$this->pos];
        $this->reserved_keywords[SIP_BEGIN] = new Token(SIP_BEGIN, 'BEGIN');
        $this->reserved_keywords[SIP_END] = new Token(SIP_END, 'END');
    }
    
    public function error()
    {
        throw new \Exception('Invalid character: ' . $this->current_char);
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
        
        return $this;
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
     * return the next character from the text buffer without incrementing the self.pos variable
     */
    public function peek()
    {
        $peek_pos = $this->pos + 1;
        if ($peek_pos > strlen($this->text) - 1) {
            return null;
        }
        
        return $this->text[$peek_pos];
    }
    
    /**
     * handle identifiers and reserved keywords
     */
    public function id()
    {
        $result = '';
        while ($this->current_char && ctype_alnum($this->current_char)) {
            $result .= $this->current_char;
            $this->advance();
        }
        
        if (isset($this->reserved_keywords[$result])) {
            return $this->reserved_keywords[$result];
        }
        
        return new Token(SIP_ID, $result);
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
            
            if (ctype_alpha($this->current_char)) {
                return $this->id();
            }
            
            if ($this->current_char == ":" && $this->peek() == "=") {
                $this->advance()->advance();
                return new Token(SIP_ASSIGN, ':=');
            }
            
            if ($this->current_char == ';') {
                $this->advance();
                return new Token(SIP_SEMI, ';');
            }
            
            if ($this->current_char == '.') {
                $this->advance();
                return new Token(SIP_DOT, '.');
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

/**
 * represents a 'BEGIN ... END' block
 */
class Compound extends AST
{
    public $children = [];

    public function __construct()
    {
        //
    }
}

class Assign extends AST
{
    private $left;

    private $op;

    private $right;

    public function __construct($left, $op, $right)
    {
        $this->left = $left;
        $this->op = $op;
        $this->right = $right;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

/**
 * The Variable node is constructed out of ID token
 */
class Variable extends AST
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

/**
 * yes, we do nothing
 */
class NoOp extends AST
{
    //
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
    
    public function assert_token_type($expected, $actual)
    {
        if ($expected != $actual) {
            throw new \Exception("Invalid syntax, expected {$expected} but {$actual} found!");
        }
    }

    /**
     * compare the current token type with the passed token
     * type and if thet match then "eat" the current token,
     * and assign the next token to the this.current_token,
     * otherwise throw an error
     */
    public function eat($token_type)
    {
        $this->assert_token_type($token_type, $this->current_token->type);
        $this->current_token = $this->lexer->get_next_token();
    }
    
    /**
     * program: compound_statement DOT
     */
    public function program()
    {
        $node = $this->compound_statement();
        $this->eat(SIP_DOT);
        return $node;
    }
    
    /**
     * compound_statement: BEGIN statement_list END
     */
    public function compound_statement()
    {
        $this->eat(SIP_BEGIN);
        $nodes = $this->statement_list();
        $this->eat(SIP_END);
        
        $root = new Compound();
        foreach ($nodes as $node) {
            $root->children[] = $node;
        }
        
        return $root;
    }
    
    /**
     * statement_list: statement | statement SEMI statement_list
     */
    public function statement_list()
    {
        $statement = $this->statement();
        $results = [$statement];
        
        while ($this->current_token->type == SIP_SEMI) {
            $this->eat(SIP_SEMI);
            $results[] = $this->statement();
        }

        if ($this->current_token->type == SIP_ID) {
            $this->error();
        }
        
        return $results;
    }
    
    /**
     * statement: compound_statement | assignment_statement | empty
     */
    public function statement()
    {
        if ($this->current_token->type == SIP_BEGIN) {
            return $this->compound_statement();
        }
        
        if ($this->current_token->type == SIP_ID) {
            return $this->assignment_statement();
        }
        
        return $this->empty();
    }
    
    /**
     * assignment_statement: variable ASSIGN expr
     */
    public function assignment_statement()
    {
        $left = $this->variable();
        $token = $this->current_token;
        $this->eat(SIP_ASSIGN);
        $right = $this->expr();
        return new Assign($left, $token, $right);
    }
    
    /**
     * variable: ID
     */
    public function variable()
    {
        $node = new Variable($this->current_token);
        $this->eat(SIP_ID);
        return $node;
    }
    
    /**
     * empty:
     */
    public function empty()
    {
        return new NoOp();
    }

    /**
     * factor: (PLUS|MINUS) factor | INTEGER | LPAREN expr RPAREN | variable
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
        } else {
            return $this->variable();
        }
    }

    /**
     * term: factor ((MUL/DIV) factor)*
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
     * expr: term ((PLUS/MINUS) term)*
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
        $node = $this->program();
        $this->assert_token_type(SIP_EOF, $this->current_token->type);
        return $node;
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
    
    private $GLOBAL_SCOPE = [];

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
    
    public function visit_compound(Compound $node)
    {
        foreach ($node->children as $child) {
            $this->visit($child);
        }
    }
    
    public function visit_assign(Assign $node)
    {
        $var_name = $node->left->value;
        $this->GLOBAL_SCOPE[$var_name] = $this->visit($node->right);
    }
    
    public function visit_variable(Variable $var)
    {
        $var_name = $var->value;
        if (isset($this->GLOBAL_SCOPE[$var_name])) {
            return $this->GLOBAL_SCOPE[$var_name];
        }

        throw new \Exception("Undefined variable {$var_name}.");
    }

    public function visit_no_op(NoOp $node)
    {
        // do nothing
    }

    public function interpret()
    {
        $tree = $this->parser->parse();
        $result = $this->visit($tree);
        var_dump($this->GLOBAL_SCOPE);
        return $result;
    }
}

class Sip
{
    public function interactive()
    {
        while (true) {
            try {
                fwrite(STDOUT, 'sip> ');
                $input = fgets(STDIN);
                $this->exec_string("BEGIN {$input} END.");
            } catch (Exception $ex) {
                echo $ex . PHP_EOL;
            }
        }
    }

    public function exec_string($input)
    {
        $src = str_replace(array("\r\n", "\r", "\n"), "", $input);
        $lexer = new Lexer($src);
        $parser = new Parser($lexer);
        $interpreter = new Interpreter($parser);
        $interpreter->interpret();
    }

    public function exec_file($filename)
    {
        try {
            if (!file_exists($filename)) {
                throw new \Exception("file {$filename} not found!");
            }

            $file = fopen($filename, 'r');
            $src = fread($file, filesize($filename));
            $this->exec_string($src);
            fclose($file);
        } catch (Exception $ex) {
            echo $ex . PHP_EOL;
        }
    }

    public function run()
    {
        $params = getopt('f::');
        if (isset($params['f'])) {
            $this->exec_file($params['f']);
        } else {
            $this->interactive();
        }
    }
}

$sip = new Sip();
$sip->run();
