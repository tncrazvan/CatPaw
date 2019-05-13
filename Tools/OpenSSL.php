<?php
namespace com\github\tncrazvan\CatPaw\Tools;

abstract class OpenSSL{
    /**
     * Creates the contents of a PEM certificate. Save this to a file.pem and load it lader for your socket.
     * @param array $subject an array containing fields required for the certificate.
     * Here's an example of a valid array: 
     * [
     *  "countryName" => "US",
     *   "stateOrProvinceName" => "Texas",
     *   "localityName" => "Houston",
     *   "organizationName" => "DevDungeon.com",
     *   "organizationalUnitName" => "Development",
     *   "commonName" => "DevDungeon",
     *   "emailAddress" => "nanodano@devdungeon.com"
     * ]
     * @param string $filename this will be the filename of the certificate.
     * @param int $days number of days the certificate will be valid for.
     * @param string $passphrase passphrase to be used to generate the certificate.
     * @return void
     */
    public static function mkcert(array $subject,string $filename,int $days=365,string $passphrase="") {
        //create ssl cert for this scripts life.
        
         //Create private key
         $privkey = openssl_pkey_new();
        
         //Create and sign CSR
         $cert    = openssl_csr_new($subject, $privkey);
         $cert    = openssl_csr_sign($cert, null, $privkey, $days);
        
         //Generate PEM file
         $pem = array();
         openssl_x509_export($cert, $pem[0]);
         openssl_pkey_export($privkey, $pem[1], $passphrase);
         $pem = implode($pem);
        
         //Save PEM file
         file_put_contents($filename, $pem);
         chmod($filename, 0600);
    }
}