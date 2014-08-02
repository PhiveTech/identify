<?php

// For debugging purposes:
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once( __DIR__ . "/identify/identify_client.php" );

// If the client is receiving a response...
if ( isset( $_POST['response'] ) ) { 
    $response = processResponse( $_POST['response'] );
    // Do whatever you want to do with the response
    var_dump( $response );
    // Then...
    die();
}

// Otherwise, redirect for validation:
makeRequest( "http://phive-alpha.mit.edu/gitignored/test.php" ); /* or whatever the url of this page (or the page which should receive the response) is */

?>