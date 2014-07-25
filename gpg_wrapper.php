<?php

/*$key = __DIR__ . "/../clientCerts/public.key";
$keySecret = __DIR__ . "/../clientCerts/private.key";
$keyPass = __DIR__ . "/../clientCerts/private.pass";
$original = "test123";
$encrypted = encrypt( $key, $original );
echo $encrypted;
$decrypted = decrypt( $keySecret, $keyPass, $encrypted );
echo $decrypted;
if ( $original != $decrypted ) {
    throw new Exception("Decrypted did not match original text");
}*/


function hex2bin2($h) {
    if (!is_string($h)) return null;
    $r='';
    for ($a=0; $a<strlen($h)-1; $a+=2) { $r.=chr(hexdec($h{$a}.$h{($a+1)})); }
    return $r;
}


function import( $keyFile, $private ) {
    // echo $key;
    // echo escapeshellarg( $key );
    $privateArg = "0";
    if ( $private ) {
        $privateArg = "1";
    }
    $import = shell_exec( __DIR__ . '/shell/import.sh ' . escapeshellarg( $keyFile ) . ' ' . $privateArg . ' 2>&1; echo $?' ); //2>&1; echo $?" );
    // regex for in quotes taken from http://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
    // First line of returned for not changed:  gpg: key AC251F36: "Phive Alpha (identify client key) <phive-alpha@mit.edu>" not changed
    // First line of returned for imported:  gpg: key AC251F36: public key "Phive Alpha (identify client key) <phive-alpha@mit.edu>" imported
    $patternNotChanged = '/gpg: key ([A-F0-9]+): "([^"\\\\]++|\\\\.)*" not changed/';
    //gpg: key B494C73D: public key "Kenneth Leidal (identify server key) <kkleidal@mit.edu>" imported
    $patternImported = '/gpg: key ([A-F0-9]+): public key "([^"\\\\]++|\\\\.)*" imported/';
    if ( $private ) {
        $patternNotChanged = '/gpg: key ([A-F0-9]+): already in (secret) keyring/';
        // gpg: key B494C73D: secret key imported
        $patternImported = '/gpg: key ([A-F0-9]+): (secret) key imported/';
    }
    preg_match( $patternImported, $import, $matchImported );
    $keySignature;
    $keyName;

    $gpgKey = null;
    $toCheck = Array( $patternNotChanged, $patternImported );
    for ( $i = 0; $i < count( $toCheck ); $i += 1 ) {
        $pattern = $toCheck[$i];
        $str = $import;
        preg_match( $pattern, $str, $match );
        if ( count( $match ) === 0 ) {
            return false;
        }
        $keySignature = $match[1];
        $keyName = str_replace( "\\\"", "\"", $match[2] );
        $keyName = str_replace( "\\\\", "\\", $keyName );
        $match = Array (
            "signature" => $keySignature,
            "name" => $keyName
        );
        if ( $match ) {
            $gpgKey = $match;
            break;
        }
    }

    return $gpgKey;
}

// Note:  this isn't safe.  Especially if someone tries to do it with the server's public key. TODO:  Make sure it's not the server's public key
/* function deleteKey( $key ) {
    if ( ! $key ) {
        return;
    }
    print $key['signature'];
    $status = shell_exec( 'gpg --yes --delete-key ' . escapeshellarg( $key['signature'] ) );
    echo $status;
    return $status;
} */

function rm( $file ) {
    shell_exec( "rm -f " . escapeshellarg( $file ) );
}

function gpgEncrypt( $gpgKey, $content ) {
    // echo $key;
    // echo escapeshellarg( $key );
    $decryptedFile = tempnam( __DIR__ . "/temp", "dec" );
    $encryptedFile = tempnam( __DIR__ . "/temp", "enc" );
    file_put_contents( $decryptedFile, $content );
    shell_exec( __DIR__ . '/shell/encrypt.sh ' . escapeshellarg( $gpgKey['signature'] ) . ' ' . escapeshellarg( $decryptedFile ) . ' ' . escapeshellarg( $encryptedFile ) . ' 2>&1; echo $?' ); //2>&1; echo $?" );
    rm( $decryptedFile );
    $encrypted = file_get_contents( $encryptedFile );
    rm( $encryptedFile );
    return bin2hex( $encrypted );
}

function gpgDecrypt( $gpgKey, $passphraseFile, $encrContent ) {    
    $decryptedFile = tempnam( __DIR__ . "/temp", "dec" );
    $encryptedFile = tempnam( __DIR__ . "/temp", "enc" );
    file_put_contents( $encryptedFile, hex2bin2( $encrContent ) );
    shell_exec( __DIR__ . '/shell/decrypt.sh ' . escapeshellarg( $gpgKey['signature'] ) . ' ' . $passphraseFile . ' ' . escapeshellarg( $encryptedFile ) . ' ' . escapeshellarg( $decryptedFile ) . ' 2>&1; echo $?' );
    rm( $encryptedFile );
    $decrypted = file_get_contents( $decryptedFile );
    rm( $decryptedFile );
    return $decrypted;
}

function encrypt( $keyFile, $content ) {
    $gpgKey = import( $keyFile, false );
    return gpgEncrypt( $gpgKey, $content );
}

function decrypt( $keyFile, $keyPass, $encrContent ) {
    $gpgKey = import( $keyFile, true );
    return gpgDecrypt( $gpgKey, $keyPass, $encrContent );
}
//echo file_put_contents($file, $key);
//$import = shell_exec("gpg --import " . $file);
//echo $import;
/*
echo $key;
$string = "test";
$string = str_replace("\\", "\\\\", $string);
$string = str_replace("\"", "\\\"", $string);
$out = shell_exec("echo \"" . $string . "\" | gpg --encrypt --armor -r B494C73D" );
echo $out;*/
?>
