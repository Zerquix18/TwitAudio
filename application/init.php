<?php
/*
	init.php
	Initializes everything
	a catches the exceptions
 */
require DOCUMENT_ROOT . '/application/exceptions/ProgrammerException.php';
require DOCUMENT_ROOT . '/application/exceptions/ValidationException.php';
require DOCUMENT_ROOT . '/application/exceptions/DBException.php';
require DOCUMENT_ROOT . '/application/exceptions/VendorException.php';

spl_autoload_register( function ( $name ) {
	$file = str_replace('\\', '/', $name);
	$file = DOCUMENT_ROOT . '/' . $file . '.php';
	if( is_readable( $file ) ) {
		require $file;
	}
});

try {
	ob_start('minify_html');
	$is_production = \Config::get('is_production');
	if( $is_production ) {
		error_reporting(0);
	} else {
		error_reporting(E_ALL);
	}

	db_init();
	session_init();
	
	$is_logged = is_logged(); // returns the user ID or 0
	if( $is_logged ) {
		$_USER = $db->query("SELECT * FROM users WHERE id = {$is_logged}");
	} else {
		$_USER = null;
	}

	require DOCUMENT_ROOT . '/application/router.php';

} catch ( \ProgrammerException $e ) {
	$message  = '<strong>' . $e->getMessage() . '</strong><br>' . "\n";
	$message .= nl2br($e->getTraceAsString());
} catch ( \VendorException $e ) {
	$message  = 'Error with ' . $e->vendor . ': ' . $e->getMessage();
} catch ( \DBException $e ) {
	$message  = $e->getMessage() . ': '; // <- must say where
	$message .= db()->error;
	$message .= db()->query ? ' [ ' . db()->query . ' ]' : '';
} catch ( \ValidationException $e ) {
	/** this exception must not be catched here */
	$message  = 'ValidationException must have been catched before.';
	$message .= $e->getTraceAsString();
} catch( \Exception $e ) {
	$message  = $e->getMessage();
} finally {
	if( isset($message) ) {
		if( $is_production ) {
			ob_end_clean();
			/**
			 * @todo mail me
			 */
			View::exit_500();
		}
		exit($message);
	}
}