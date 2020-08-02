<?php

abstract class HttpMethodSwitcher{
    public abstract function GET();
    public abstract function POST();
    public abstract function PUT();
    public abstract function PATCH();
    public abstract function DELETE();
    public abstract function COPY();
    public abstract function HEAD();
    public abstract function OPTIONS();
    public abstract function LINK();
    public abstract function UNLINK();
    public abstract function PURGE();
    public abstract function LOCK();
    public abstract function UNLOCK();
    public abstract function PROPFIND();
    public abstract function VIEW();
}