<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once( __DIR__ . '/config.php' );
require_once( __DIR__ . '/gpg_wrapper.php' );

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

function deleteOld( $con ) {
	if ( ! $con->query( "DELETE FROM `" . SQL_IDENTIFY_TABLE . "` WHERE `request_time` < DATE_SUB( NOW(), INTERVAL 30 MINUTE )" ) ) {
		echo "Deleting tables failed: (" . $mysqli->errno . ") " . $mysqli->error;
		die();
	}
}

function makeConnection() {
	$con = new mysqli( SQL_URI, SQL_USER, SQL_PASS, SQL_DB );

	if ($con->connect_errno) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
		die();
	}

	return $con;
}

function logToken( $token ) {
	$con = makeConnection();

	deleteOld( $con );

	if (!($stmt = $con->prepare("INSERT INTO `" . SQL_IDENTIFY_TABLE . "` (`token`, `ip`, `httpUserAgent`) VALUES (?, ?, ?)"))) {
		echo "Prepare failed: (" . $mysqli->errno . ") " . $con->error;
		die();
	}

	if (!$stmt->bind_param("sss", $token, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ) ) {
		echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
		die();
	}

	if (!$stmt->execute()) {
		echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		die();
	}

	$con->close();
}

function validateToken( $token, $remoteAddr, $httpUserAgent ) {
	$con = makeConnection();

	deleteOld( $con );

	if (!($stmt = $con->prepare("SELECT `ip`, `httpUserAgent` FROM `" . SQL_IDENTIFY_TABLE . "` WHERE `token`=?"))) {
		echo "Prepare failed: (" . $mysqli->errno . ") " . $con->error;
		die();
	}

	if (!$stmt->bind_param("s", $token)) {
		echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
		die();
	}

	if (!$stmt->execute()) {
		echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
		die();
	}

	if (!$stmt->bind_result( $sqlIp, $sqlHUA )) {
		echo "Bind result failed: (" . $stmt->errno . ") " . $stmt->error;
		die();
	}

	$found = false; 
	while ( $stmt->fetch() ) {
		if ( $found ) {
			break;
		}
		if ( $sqlIp == $_SERVER['REMOTE_ADDR'] && $sqlIp == $remoteAddr && $sqlHUA == $_SERVER['HTTP_USER_AGENT'] && $sqlHUA == $httpUserAgent ) {
			$found = true;
		}
	}

	if ( $found ) {
		if ( ! $prepClean = $con->prepare( "DELETE FROM `" . SQL_IDENTIFY_TABLE . "` WHERE `token`=?" ) ) {
			echo "Prepare failed: (" . $mysqli->errno . ") " . $con->error;
			die();
		}
		if ( ! $prepClean->bind_param("s", $token) ) {
			echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
			die();
		}
		if ( ! $prepClean->execute() ) {
			echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			die();
		}
	}

	$con->close();

	return $found;
}

function makeRequest( $redirectLocation ) {
	$token = generateToken();
	$request = Array(
		'redirect' => $redirectLocation,
		'publickey' => file_get_contents( PATH_CLIENT_PUBLIC_KEY ),
		'token' => $token
	);

	logToken( $token );

	$out = encrypt( PATH_SERVER_PUBLIC_KEY, serialize( $request ) );
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

function processResponse( $response ) {
	$data = false;
	try {
		$data = unserialize( decrypt( PATH_CLIENT_PRIVATE_KEY, PATH_CLIENT_PRIVATE_KEY_PASS, $response ) );
	} catch( Exception $e ) {
		return false;
	}
	if ( ! $data['response']['hasCert'] ) {
		return false;
	}
	$valid = validateToken( $data['token'], $data['response']['remoteAddr'], $data['response']['httpUserAgent'] );
	if ( ! $valid ) {
		return false;
	}
	return $data['response'];
}

?>