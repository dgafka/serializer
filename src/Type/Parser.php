<?php

declare(strict_types=1);

namespace JMS\Serializer\Type;

use JMS\Serializer\Type\Exception\SyntaxError;

class Parser implements ParserInterface
{
    /**
     * @var Lexer
     */
    private $lexer;

    public function parse(string $string): array
    {
        $this->lexer = new Lexer();
        $this->lexer->setInput($string);
        $this->lexer->moveNext();
        return $this->visit();
    }

    /**
     * @return mixed
     */
    private function visit()
    {
        $this->lexer->moveNext();

        if (!$this->lexer->token) {
            throw new SyntaxError(
                'Syntax error, unexpected end of stream'
            );
        }

        if (Lexer::T_FLOAT === $this->lexer->token['type']) {
            return floatval($this->lexer->token['value']);
        } elseif (Lexer::T_INTEGER === $this->lexer->token['type']) {
            return intval($this->lexer->token['value']);
        } elseif (Lexer::T_NULL === $this->lexer->token['type']) {
            return null;
        } elseif (Lexer::T_STRING === $this->lexer->token['type']) {
            return $this->lexer->token['value'];
        } elseif (Lexer::T_IDENTIFIER === $this->lexer->token['type']) {
            if ($this->lexer->isNextToken(Lexer::T_TYPE_START)) {
                return $this->visitCompoundType();
            } elseif ($this->lexer->isNextToken(Lexer::T_ARRAY_START)) {
                return $this->visitArrayType();
            }
            return $this->visitSimpleType();
        } elseif (Lexer::T_ARRAY_START === $this->lexer->token['type']) {
            return $this->visitArrayType();
        }

        throw new SyntaxError(sprintf(
            'Syntax error, unexpected "%s" (%s)',
            $this->lexer->token['value'],
            $this->getConstant($this->lexer->token['type'])
        ));
    }

    /**
     * @return string|mixed[]
     */
    private function visitSimpleType()
    {
        $value = $this->lexer->token['value'];
        return ['name' => $value, 'params' => []];
    }

    private function visitCompoundType(): array
    {
        $name = $this->lexer->token['value'];
        $this->match(Lexer::T_TYPE_START);

        $params = [];
        if (!$this->lexer->isNextToken(Lexer::T_TYPE_END)) {
            while (true) {
                $params[] = $this->visit();

                if ($this->lexer->isNextToken(Lexer::T_TYPE_END)) {
                    break;
                }
                $this->match(Lexer::T_COMMA);
            }
        }
        $this->match(Lexer::T_TYPE_END);
        return [
            'name' => $name,
            'params' => $params,
        ];
    }

    private function visitArrayType(): array
    {
//        $this->match(Lexer::T_ARRAY_START);

        $params = [];
        if (!$this->lexer->isNextToken(Lexer::T_ARRAY_END)) {
            while (true) {
                $params[] = $this->visit();
                if ($this->lexer->isNextToken(Lexer::T_ARRAY_END)) {
                    break;
                }
                $this->match(Lexer::T_COMMA);
            }
        }
        $this->match(Lexer::T_ARRAY_END);
        return $params;
    }

    private function match(int $token): void
    {
        if (!$this->lexer->lookahead) {
            throw new SyntaxError(
                sprintf('Syntax error, unexpected end of stream, expected %s', $this->getConstant($token))
            );
        }

        $lookaheadType = $this->lexer->lookahead['type'];

        // Short-circuit on first condition, usually types match
        if ($lookaheadType === $token) {
            $this->lexer->moveNext();

            return;
        }

        throw new SyntaxError(sprintf(
            'Syntax error, unexpected "%s" (%s), expected was %s',
            $this->lexer->lookahead['value'],
            $this->getConstant($this->lexer->lookahead['type']),
            $this->getConstant($token)
        ));
    }

    private function getConstant(int $value): string
    {
        $oClass = new \ReflectionClass(Lexer::class);
        return array_search($value, $oClass->getConstants());
    }
}
