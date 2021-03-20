<?php

namespace net\razshare\catpaw\attributes\metadata;

class Meta{
    public static $PATH_PARAMS = [];

    public static array $HTTP_METHODS_PATHS_PATTERNS = [];

    public static array $METHODS = [];
    public static array $METHODS_ATTRIBUTES = [];

    public static array $METHODS_ARGS = [];
    public static array $METHODS_ARGS_NAMES = [];
    public static array $METHODS_ARGS_ATTRIBUTES = [];

    public static array $FUNCTIONS = [];
    public static array $FUNCTIONS_ATTRIBUTES = [];

    public static array $FUNCTIONS_ARGS = [];
    public static array $FUNCTIONS_ARGS_NAMES = [];
    public static array $FUNCTIONS_ARGS_ATTRIBUTES = [];


    public static array $FILTERS = [];
    public static array $FILTERS_ATTRIBUTES = [];

    public static array $FILTERS_ARGS = [];
    public static array $FILTERS_ARGS_NAMES = [];
    public static array $FILTERS_ARGS_ATTRIBUTES = [];


    public static array $KLASS = [];
    public static array $CLASS_ATTRIBUTES = [];

    public static function reset():void{
        static::$PATH_PARAMS = [];

        static::$METHODS = [];
        static::$METHODS_ATTRIBUTES = [];

        static::$METHODS_ARGS = [];
        static::$METHODS_ARGS_NAMES = [];
        static::$METHODS_ARGS_ATTRIBUTES = [];

        static::$FUNCTIONS = [];
        static::$FUNCTIONS_ATTRIBUTES = [];

        static::$FUNCTIONS_ARGS = [];
        static::$FUNCTIONS_ARGS_NAMES = [];
        static::$FUNCTIONS_ARGS_ATTRIBUTES = [];


        static::$FILTERS = [];
        static::$FILTERS_ATTRIBUTES = [];

        static::$FILTERS_ARGS = [];
        static::$FILTERS_ARGS_NAMES = [];
        static::$FILTERS_ARGS_ATTRIBUTES = [];


        static::$KLASS = [];
        static::$CLASS_ATTRIBUTES = [];
    }
}