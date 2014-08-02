<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once( __DIR__ . "/config.php" );
require_once( __DIR__ . "/gpg_wrapper.php" );

/*if ( isset( $_POST['response'] ) ) {
    var_dump( $_POST );
    die();
}*/

if ( ! isset( $_POST['request'] ) ) {
    print "error";
    die();
}

function myDecrypt( $data ) {
    return decrypt( PATH_SERVER_PRIVATE_KEY, PATH_SERVER_PRIVATE_KEY_PASS, $data );
}

$inputText = myDecrypt( $_POST['request'] );
$input = unserialize( $inputText );

if ( ! isset( $input['redirect'] ) || ! isset( $input['publickey'] ) || ! isset( $input['token'] ) ) {
    print "error";
    die();
}

$redirectLocation = $input['redirect'];
$destinationKey = tempnam( __DIR__ . "/temp", "key" );
file_put_contents( $destinationKey, $input['publickey'] );
$token = $input['token'];

$hasCert = false;
if ( isset( $_SERVER['SSL_CLIENT_S_DN_CN'] ) ) {
    $hasCert = true;
}

$userDetails = array(
    'hasCert' => $hasCert,
    'httpUserAgent' => $_SERVER['HTTP_USER_AGENT'],
    'remoteAddr' => $_SERVER['REMOTE_ADDR'],
);

if ( $hasCert ) {
    $userDetails['cert'] = array (
        'name' => $_SERVER['SSL_CLIENT_S_DN_CN'],
        'email' => $_SERVER['SSL_CLIENT_S_DN_Email'],
        'issuer' => $_SERVER['SSL_CLIENT_I_DN_O'],
        'expires' => $_SERVER['SSL_CLIENT_V_END']
    );
}

$response = array (
    'token' => $token,
    'response' => $userDetails
);

// echo serialize( $userDetails );
$out = encrypt( $destinationKey, serialize( $response ) );
// echo $out;
shell_exec( "rm -f " . escapeshellarg( $destinationKey ) );

$redir = str_replace( "\\", "\\\\", $redirectLocation );
$redir = str_replace( "\"", "\\\"", $redir );

?>

<html>
<head>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript">
$( function() {
    $('<form>').attr( "action", "<?php echo $redir; ?>" ).attr( "method", "post" )
        .append( $("<input>").attr( "type", "hidden" ).attr( "name", "response" ).val( "<?php echo $out; ?>" ) )
        .submit();
} );
</script>

</head>
<body>
<?php
if ( ! $hasCert ) {
    ?><h1>I don't recognize you...</h1><?php
} else {
    ?><h1>Hello, <?php echo $_SERVER['SSL_CLIENT_S_DN_CN']; ?>.  Please allow me to point you in the right direction...</h1><?php
}
?>
</body>
</html>
