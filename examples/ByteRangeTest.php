<?php
namespace examples;

use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\http\ResponseHeaders;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Produces;
use net\razshare\catpaw\services\ByteRangeService;

#[Path("/byte-range")]
#[Produces("audio/mpeg")]
class ByteRangeTest{
    
    #[GET]
    public function test(
        #[Inject] ByteRangeService $range,
        #[ResponseHeaders] array &$headers
    ){
        $filename = dirname(__FILE__)."/files/test.html";

        $headers["Content-Type"] = "text/html";

        return $range->from($filename);
    }
}