<?php

/* REQUIRED ON BOTH SERVER AND CLIENT: */
define( "IDENTIFY_DEBUG", "1" ); /* Whether or not to show debugging messages.  1 for yes, 0 for no */
define( "IDENTIFY_PARTY", "CLIENT" ); /* CLIENT if this is the client, SERVER if it is the server */

/* REQUIRED ON SERVER: */
define( "PATH_SERVER_PRIVATE_KEY", "/some/path/to/server_private.key" );
define( "PATH_SERVER_PRIVATE_KEY_PASS", "/some/path/to/server_private.pass" ); /* text file containing private key's password. */

/* REQUIRED ON CLIENT: */
define( "PATH_SERVER_PUBLIC_KEY", "/some/path/to/server_public.key" );
define( "PATH_CLIENT_PRIVATE_KEY", "/some/path/to/client_private.key" );
define( "PATH_CLIENT_PRIVATE_KEY_PASS", "/some/path/to/client_private.pass" ); /* text file containing private key's password. */
define( "PATH_CLIENT_PUBLIC_KEY", "/some/path/to/client_public.key" );

define( "IDENTIFY_SERVER_URL", "https://tbeaver.scripts.mit.edu:444/identify/");

define( "SQL_URI", "localhost" );
define( "SQL_USER", "{username}" );
define( "SQL_PASS", "{password}" );
define( "SQL_DB", "{database name}" );
define( "SQL_IDENTIFY_TABLE", "{table name for identify--must be pre-configured}" );

?>
