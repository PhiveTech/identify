<?php

require_once( __DIR__ . '/config.php' );
require_once( __DIR__ . '/gpg_wrapper.php' );

// var_dump( $_POST );
if ( isset( $_POST['response'] ) ) {
	verifyResponse( $_POST['response'] );
	die();
}

/* Requires the following constants to be set:
   SQL_URI
   SQL_USER
   SQL_PASS
   SQL_DB
   SQL_IDENTIFY_TABLE

   IDENTIFY_SERVER_URL

   PATH_CLIENT_PRIVATE_KEY
   PATH_CLIENT_PRIVATE_KEY_PASS
   PATH_CLIENT_PUBLIC_KEY
   PATH_SERVER_PUBLIC_KEY
   */

define( "TOKEN_LENGTH", 128 );

function generateToken() {
	return bin2hex( openssl_random_pseudo_bytes( TOKEN_LENGTH ) );
}

function makeRequest( $redirectLocation ) {
	$token = generateToken();
	$request = Array(
		'redirect' => $redirectLocation,
		'publickey' => file_get_contents( PATH_CLIENT_PUBLIC_KEY ),
		'token' => $token
	);
	// echo serialize($request);
	$out = encrypt( PATH_SERVER_PUBLIC_KEY, serialize( $request ) );
	// echo "Out: " . $out;
	?>

<html>
<head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript">
$( function() {
    $('<form>').attr( "action", "<?php echo IDENTIFY_SERVER_URL ?>" ).attr( "method", "post" )
        .append( $("<input>").attr( "type", "hidden" ).attr( "name", "request" ).val( "<?php echo $out; ?>" ) )
        .submit();
} );
</script>
</head>
<body>
<h1>Redirecting for verification...</h1>
</body>
</html>

	<?php

	die();
}

function verifyResponse( $response ) {
	$data = false;
	try {
		$data = unserialize( decrypt( PATH_CLIENT_PRIVATE_KEY, PATH_CLIENT_PRIVATE_KEY_PASS, $response ) );
	} catch( Exception $e ) {
		// echo "Exception: ";
		// echo $e;
		return false;
	}
	if ( ! $data['hasCert'] ) {
		return false;
	}
	// TODO:  Check to see if bytes match
	var_dump( $data );
	return $data;
}

makeRequest( "https://phive-alpha.mit.edu/identify_test/identify_client.php" );

?>