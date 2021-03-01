<?php

namespace com\github\tncrazvan\catpaw\qb\tools;

interface QueryConst{
    public const 
        VARIABLE_SYMBOL = ':',
        PARENTHESIS_LEFT = '(',
        PARENTHESIS_RIGHT = ')',
        INSERT = 'insert',
        DELETE = 'delete',
        INTO = 'into',
        UPDATE = 'update',
        SET = 'set',
        VALUE = 'value',
        VALUES = 'values',
        SELECT = 'select',
        PERIOD = '.',
        COMMA = ',',
        FROM = 'from',
        WHERE = 'where',
        JOIN = 'join',
        LEFT_JOIN = 'left join',
        RIGHT_JOIN = 'right join',
        INNER_JOIN = 'inner join',
        ON = 'on',
        AND = 'and',
        AS = 'as',
        OR = 'or',
        GREATER_THAN = '>',
        GREATER_THAN_EQUALE = '>=',
        LESSER_THAN = '<',
        LESSER_THAN_EQUAL = '<=',
        EQUALS = '=',
        LIKE = 'like',
        BETWEEN = 'between',
        LIMIT = 'limit'
    ;
}
