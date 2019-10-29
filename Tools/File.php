<?php
namespace com\github\tncrazvan\CatPaw\Tools;
/**
 * @var string $filename name of the file
 */
class File
{
  private $filename;
  function __construct(string $filename)
  {
    $this->filename = $filename;
  }
}
