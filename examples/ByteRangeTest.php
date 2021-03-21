<?php
namespace examples;

use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\Produces;
use net\razshare\catpaw\tools\response\ByteRangeResponse;

#[Path("/byte-range")]
#[Produces("text/html")]
class ByteRangeTest{
    
    #[GET]
    public function test():ByteRangeResponse{
        $filename = dirname(__FILE__)."/files/test.html";
        return ByteRangeResponse::from($filename);
    }
}