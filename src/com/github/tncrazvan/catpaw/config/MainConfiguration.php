<?php
namespace com\github\tncrazvan\catpaw\config;

abstract class MainConfiguration{

    /**
     * Full uri in the form of **{scheme?}{hostname}:{port}** the server should use.
     * For example **localhost:8080** or **tcp://localhost:8080**.
     */
    public string $uri = 'localhost:8080';

    /**
     * Middlewares to employ while processing requests.
     * @see https://reactphp.org/http/#server-usage
     */
    public array $middlewares = [];

    /**
     * Context configuration for the server.
     * @see https://reactphp.org/http/#listen
     */
    public array $context = [];

    /**
     * This will allow you to manage your sessions directly instead of saving them on a ram disk(which is the default).
     * Note that setting this property will disable ram disk sessions.
     */
    public ?\SessionHandlerInterface $session_handler = null;

    /**
     * Session will be saved to this directory.
     * Default value is **.sessions**
     */
    public string $session_directory = '.sessions';


    /**
     * This is the size of the ram disk which holds your session expressed in MBs.
     * The session will grow if more space is needed, this is an initialization value.
     * Default value is **512**
     */
    public int $session_size = 512;

    /**
     * This is the time to live of any session expressed in seconds.
     * Default value is **1440** (24 minutes).
     */
    public int $session_ttl = 1_440;
}
