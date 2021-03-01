<?php
namespace com\github\tncrazvan\catpaw\qb\tools;

use Exception;

class Page{
    private function __construct(
        private int $offset,
        private int $size,
        private bool $sqlsrv
    ){
        if($size <= 0)
            throw new Exception("Size of page cannot be 0 or less.");
        if($offset < 0)
            throw new Exception("Offset of page cannot be less then 0");
    }

    public function isSqlsrv():bool{
        return $this->isSqlsrv();
    }

    /**
     * Get the **offset** of this page.
     * Note: offsets start from 0.
     */
    public function getNextOffset():int{
        return $this->size * $this->offset;
    }

    /**
     * Get the **maximum number of elements** this page can contain.
     */
    public function getSize():int{
        return $this->size;
    }

    /**
     * Get both the **offset** and the **size** of the page as an array (**in that order**).
     * @return array offset and size of the page
     */
    public function get():array{
        return [
            $this->size * $this->offset,
            $this->size
        ];
    }

    /**
     * Make a new Page of size **$size** that starts at offset 0.
     * @param int $size maximum number of elements the page can contain.
     * @return Page the newly created page object.
     */
    public static function ofSize(int $size, bool $sqlsrv = false):Page{
        return new Page(0,$size,$sqlsrv);
    }

    /**
     * Make a new Page of size **$size** that starts at offset **$offset**.
     * @param int $offset offset of the page (starting from 0).
     * @param int $size maximum number of elements the page can contain.
     * @return Page the newly created page object.
     */
    public static function of(int $offset, int $size, bool $sqlsrv = false):Page{
        return new Page($offset,$size,$sqlsrv);
    }
}