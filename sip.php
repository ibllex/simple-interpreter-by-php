#!/usr/bin/env php

<?php

// SIP is abbreviation of 'simple interpreter by php' or just 'simple'

/**
 * LEXER
 */

// Token types
const SIP_INTEGER_CONST = 'INTEGER_CONST';
const SIP_INTEGER = 'INTEGER';
const SIP_REAL_CONST = 'REAL_CONST';
const SIP_REAL = 'REAL';
const SIP_PLUS = 'PLUS';
const SIP_MINUS = 'MINUS';
const SIP_MUL = 'MUL';
const SIP_DIV = 'DIV';
const SIP_FLOAT_DIV = 'FLOAT_DIV';
const SIP_COLON = 'COLON';
const SIP_COMMA = 'COMMA';
const SIP_LPAREN = 'LPAREN';
const SIP_RPAREN = 'RPAREN';
const SIP_BEGIN = 'BEGIN';
const SIP_END = 'END';
const SIP_PROGRAM = 'PROGRAM';
const SIP_PROCEDURE = 'PROCEDURE';
const SIP_VAR = 'VAR';
const SIP_DOT = 'DOT';
const SIP_ASSIGN = 'ASSIGN';
const SIP_SEMI = 'SEMI';
const SIP_ID = 'ID';
const SIP_EOF = 'EOF';

// Global vars
const SIP_VAR_WHITESPACE = ' ';

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
        $this->reserved_keywords[SIP_PROGRAM] = new Token(SIP_PROGRAM, 'PROGRAM');
        $this->reserved_keywords[SIP_VAR] = new Token(SIP_VAR, 'VAR');
        $this->reserved_keywords[SIP_DIV] = new Token(SIP_DIV, 'DIV');
        $this->reserved_keywords[SIP_BEGIN] = new Token(SIP_BEGIN, 'BEGIN');
        $this->reserved_keywords[SIP_END] = new Token(SIP_END, 'END');
        $this->reserved_keywords[SIP_INTEGER] = new Token(SIP_INTEGER, 'INTEGER');
        $this->reserved_keywords[SIP_REAL] = new Token(SIP_REAL, 'REAL');
        $this->reserved_keywords[SIP_PROCEDURE] = new Token(SIP_PROCEDURE, 'PROCEDURE');
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
        while ($this->current_char != null && $this->current_char == SIP_VAR_WHITESPACE) {
            $this->advance();
        }
    }
    
    public function skip_comments()
    {
        while ($this->current_char != '}') {
            $this->advance();
        }
        
        // the closing curly brace
        $this->advance();
    }

    /**
     * return a (multidigit) integer or float consumed from the input
     */
    public function number()
    {
        $result = '';
        while ($this->current_char != null && is_numeric($this->current_char)) {
            $result .= $this->current_char;
            $this->advance();
        }
        
        if ($this->current_char == '.') {
            $result .= $this->current_char;
            $this->advance();
            
            while ($this->current_char != null && is_numeric($this->current_char)) {
                $result .= $this->current_char;
                $this->advance();
            }
            
            return new Token(SIP_REAL_CONST, (float) $result);
        }

        return new Token(SIP_INTEGER_CONST, (int) $result);
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
        while ($this->current_char != null && ctype_alnum($this->current_char)) {
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
            if ($this->current_char == SIP_VAR_WHITESPACE) {
                $this->skip_whitespace();
                continue;
            }

            // skip the end of line
            if ($this->current_char == PHP_EOL) {
                $this->advance();
                continue;
            }
            
            // if the current character is a left curly brace then skip
            // the comments until right curly brace
            if ($this->current_char == '{') {
                $this->advance();
                $this->skip_comments();
                continue;
            }
            
            // maybe variable or key words
            if (ctype_alpha($this->current_char)) {
                return $this->id();
            }

            // if the current character is a digit then get multidigit
            // consumed from the input, convert it into an INTEGER token
            // or a REAL token and return the token
            if (is_numeric($this->current_char)) {
                return $this->number();
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
                return new Token(SIP_FLOAT_DIV, '/');
            }

            if ($this->current_char == '(') {
                $this->advance();
                return new Token(SIP_LPAREN, '(');
            }

            if ($this->current_char == ')') {
                $this->advance();
                return new Token(SIP_RPAREN, ')');
            }
            
            if ($this->current_char == ":") {
                
                // if the next character is '=' then means ':=',
                // return ASSIGN token
                if ($this->peek() == "=") {
                    $this->advance()->advance();
                    return new Token(SIP_ASSIGN, ':=');
                }

                $this->advance();
                // otherwise, return COLON token
                return new Token(SIP_COLON, ':');
            }
            
            if ($this->current_char == ',') {
                $this->advance();
                return new Token(SIP_COMMA, ',');
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

class Program extends AST
{
    private $name;

    private $block;

    public function __construct($name, $block)
    {
        $this->name = $name;
        $this->block = $block;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

class ProcedureDecl extends AST
{
    private $proc_name;

    private $block_node;

    public function __construct($proc_name, $block_node)
    {
        $this->proc_name = $proc_name;
        $this->block_node = $block_node;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

/**
 * Block AST node holdes declarations and a compound statement
 */
class Block extends AST
{
    private $declarations;
    
    private $compound_statement;

    public function __construct($declarations, $compound_statement)
    {
        $this->declarations = $declarations;
        $this->compound_statement = $compound_statement;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

/**
 * VarDecl AST node represents a variable type
 */
class VarDecl extends AST
{
    private $var_node;

    private $type_node;

    public function __construct(Variable $var_node, Type $type_node)
    {
        $this->var_node = $var_node;
        $this->type_node = $type_node;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

/**
 * Type AST node represents a variable type
 */
class Type extends AST
{
    private $token;

    private $value;

    public function __construct($token)
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
     * program: PROGRAM variable SEMI block DOT
     */
    public function program()
    {
        $this->eat(SIP_PROGRAM);
        $var_node = $this->variable();
        $prog_name = $var_node->value;
        $this->eat(SIP_SEMI);
        $block_node = $this->block();
        $program_node = new Program($prog_name, $block_node);
        $this->eat(SIP_DOT);
        return $program_node;
    }
    
    /**
     * block: declarations compound_statement
     */
    public function block()
    {
        $declarations = $this->declarations();
        $compound_statement_node = $this->compound_statement();
        $block = new Block($declarations, $compound_statement_node);
        return $block;
    }
    
    /**
     * declarations: VAR (variable_declaration SEMI)+ | (PROCEDURE ID SEMI block SEMI)* | empty
     */
    public function declarations()
    {
        $declarations = [];
        
        if ($this->current_token->type == SIP_VAR) {
            $this->eat(SIP_VAR);
            while ($this->current_token->type == SIP_ID) {
                $var_decl = $this->variable_declaration();
                $declarations = array_merge($declarations, $var_decl);
                $this->eat(SIP_SEMI);
            }
        }
        
        while ($this->current_token->type == SIP_PROCEDURE) {
            $this->eat(SIP_PROCEDURE);
            $proc_name = $this->current_token->value;
            $this->eat(SIP_ID);
            $this->eat(SIP_SEMI);
            $block = $this->block();
            $proc_decl = new ProcedureDecl($proc_name, $block);
            $declarations[] = $proc_decl;
            $this->eat(SIP_SEMI);
        }
        
        return $declarations;
    }
    
    /**
     * variable_declaration: ID (COMMA ID)* COLON type_spec
     */
    public function variable_declaration()
    {
        // first id
        $var_nodes = [new Variable($this->current_token)];
        $this->eat(SIP_ID);
        
        // VAR a,b,c,d ... take out b,c,d
        while ($this->current_token->type == SIP_COMMA) {
            $this->eat(SIP_COMMA);
            $var_nodes[] = new Variable($this->current_token);
            $this->eat(SIP_ID);
        }
        
        $this->eat(SIP_COLON);

        $type_node = $this->type_spec();
        $var_declarations = [];
        
        foreach ($var_nodes as $var_node) {
            $var_declarations[] = new VarDecl($var_node, $type_node);
        }
        
        return $var_declarations;
    }
    
    /**
     * type_spec: INTEGER | REAL
     */
    public function type_spec()
    {
        $token = $this->current_token;
        if ($this->current_token->type == SIP_INTEGER) {
            $this->eat(SIP_INTEGER);
        } else {
            $this->eat(SIP_REAL);
        }
        
        return new Type($token);
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
     * term: factor ((MUL/DIV/FLOAT_DIV) factor)*
     */
    public function term()
    {
        $node = $this->factor();

        while (in_array($this->current_token->type, [SIP_MUL, SIP_DIV, SIP_FLOAT_DIV])) {
            $token = $this->current_token;
            $this->eat($token->type);
            $node = new BinOp($node, $token, $this->factor());
        }
        
        return $node;
    }

    /**
     * factor: (PLUS|MINUS) factor | INTEGER_CONST | REAL_CONST | LPAREN expr RPAREN | variable
     */
    public function factor()
    {
        $token = $this->current_token;
        if (in_array($token->type, [SIP_PLUS, SIP_MINUS])) {
            $this->eat($token->type);
            return new UnaryOp($token, $this->factor());
        } elseif ($token->type == SIP_INTEGER_CONST) {
            $this->eat(SIP_INTEGER_CONST);
            return new Num($token);
        } elseif ($token->type == SIP_REAL_CONST) {
            $this->eat(SIP_REAL_CONST);
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
 * AST visitors (walkers)
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

/**
 * Symbols and symbol table
 */

class Symbol
{
    protected $name;

    protected $type;

    public function __construct($name, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public function __get($name)
    {
        return $this->{$name};
    }
}

class VariableSymbol extends Symbol
{
    public function __toString()
    {
        return "<{$this->name}:{$this->type}>";
    }
}

class BuiltinTypeSymbol extends Symbol
{
    public function __construct($name)
    {
        parent::__construct($name);
    }
    
    public function __toString()
    {
        return $this->name;
    }
}

class SymbolTable
{
    private $symbols = [];

    public function __construct()
    {
        $this->init_builtins();
    }
    
    private function init_builtins()
    {
        $this->define(new BuiltinTypeSymbol(SIP_INTEGER));
        $this->define(new BuiltinTypeSymbol(SIP_REAL));
    }
    
    public function define(Symbol $symbol)
    {
        var_dump("Define: " . $symbol);
        $this->symbols[$symbol->name] = $symbol;
    }
    
    public function lookup($name)
    {
        if (isset($this->symbols[$name])) {
            return $this->symbols[$name];
        }
        
        return null;
    }
}

class SymbolTableBuilder extends NodeVisitor
{
    private $symtab;
    
    public function __construct()
    {
        $this->symtab = new SymbolTable();
    }
    
    public function symbol_table()
    {
        return $this->symtab;
    }

    public function visit_bin_op(BinOp $node)
    {
        $this->visit($node->left);
        $this->visit($node->right);
    }
    
    public function visit_unary_op(UnaryOp $op)
    {
        $this->visit($op->right);
    }
    
    public function visit_program(Program $node)
    {
        $this->visit($node->block);
    }
    
    public function visit_block(Block $node)
    {
        foreach ($node->declarations as $declaration) {
            $this->visit($declaration);
        }
        
        $this->visit($node->compound_statement);
    }

    /**
     * add variable to symbol table
     */
    public function visit_var_decl(VarDecl $node)
    {
        $type_name = $node->type_node->value;
        $type_symbol = $this->symtab->lookup($type_name);
        
        $var_name = $node->var_node->value;
        $var_symbol = new VariableSymbol($var_name, $type_symbol);

        $this->symtab->define($var_symbol);
    }
    
    public function visit_procedure_decl(ProcedureDecl $proc)
    {
        // do nothing here
    }

    public function visit_num(Num $node)
    {
        // do nothing here
    }

    public function visit_no_op(NoOp $node)
    {
        // do nothing
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
        
        if (null == $this->symtab->lookup($var_name)) {
            throw new \Exception("Undefined variable: {$var_name}.");
        }
        
        $this->visit($node->right);
    }

    public function visit_variable(Variable $var)
    {
        $var_name = $var->value;

        if (null == $this->symtab->lookup($var_name)) {
            throw new \Exception("Undefined variable: {$var_name}.");
        }
    }
}

/**
 * INTERPRETER
 */

class Interpreter extends NodeVisitor
{
    private $tree;
    
    private $GLOBAL_SCOPE = [];

    public function __construct(AST $tree)
    {
        $this->tree = $tree;
    }
    
    public function global_memory()
    {
        return $this->GLOBAL_SCOPE;
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
            return intdiv($this->visit($node->left), $this->visit($node->right));
        } elseif ($node->op->type == SIP_FLOAT_DIV) {
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
    
    public function visit_program(Program $node)
    {
        $this->visit($node->block);
    }
    
    public function visit_block(Block $node)
    {
        foreach ($node->declarations as $declaration) {
            $this->visit($declaration);
        }
        
        $this->visit($node->compound_statement);
    }
    
    public function visit_var_decl(VarDecl $node)
    {
        // do nothing at this time
    }
    
    public function visit_procedure_decl(ProcedureDecl $proc)
    {
        // do nothing here
    }
    
    public function visit_type(Type $node)
    {
        // do nothing at this time
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

        throw new \Exception("Undefined variable: {$var_name}.");
    }

    public function visit_no_op(NoOp $node)
    {
        // do nothing
    }

    public function interpret()
    {
        $result = $this->visit($this->tree);
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
                $this->exec_string("PROGRAM interactive; BEGIN {$input} END.");
            } catch (Exception $ex) {
                echo $ex . PHP_EOL;
            }
        }
    }

    public function exec_string($input)
    {
//        $src = str_replace(array("\r\n", "\r", "\n"), "", $input);
        $src = $input;
        $lexer = new Lexer($src);
        $parser = new Parser($lexer);
        $tree = $parser->parse();
        
        $symbol_builder = new SymbolTableBuilder();
        $symbol_builder->visit($tree);
        
        var_dump("Symbol table contents: ", $symbol_builder->symbol_table());
        
        $interpreter = new Interpreter($tree);
        $interpreter->interpret();
        
        var_dump("Run-time Global memory contents: ", $interpreter->global_memory());
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
