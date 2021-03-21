<?php
namespace examples;

use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\http\ResponseHeaders;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Produces;
use net\razshare\catpaw\services\ByteRangeService;

#[Path("/byte-range")]
#[Produces("text/html")]
class ByteRangeTest{
    
    #[GET]
    public function test(
        #[Inject] ByteRangeService $range
    ){
        $filename = dirname(__FILE__)."/files/test.html";
        return $range->from($filename);
    }
}