<?php
/*
    init.php
    Initializes everything
    a catches the exceptions
 */
require DOCUMENT_ROOT . '/application/exceptions/ProgrammerException.php';
require DOCUMENT_ROOT . '/application/exceptions/ValidationException.php';
require DOCUMENT_ROOT . '/application/exceptions/VendorException.php';

spl_autoload_register(function ($name) {
    $file = str_replace('\\', '/', $name);
    $file = DOCUMENT_ROOT . '/' . $file . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

try {
    ob_start('minify_html');
    $is_production = \Config::get('is_production');
    if ($is_production) {
        error_reporting(0);
    } else {
        error_reporting(E_ALL);
    }

    db_init();
    \Sessions::init();
    
    if (is_logged()) {
        $query = db()->prepare("SELECT * FROM users WHERE id = :id");
        $query->bindValue('id', \Sessions::getUserId(), PDO::PARAM_INT);
        $query->execute();
        $_USER = $query->fetch(PDO::FETCH_ASSOC);
    } else {
        $_USER = null;
    }
    require DOCUMENT_ROOT . '/application/router.php';

} catch (\ProgrammerException $e) {
    $message  = '<strong>' . $e->getMessage() . '</strong><br>' . "\n";
    $message .= nl2br($e->getTraceAsString());
} catch (\VendorException $e) {
    $message  = 'Error with ' . $e->vendor . ': ' . $e->getMessage();
} catch (\PDOException $e) {
    $message  = $e->getMessage() . '<br>';
    $message .= nl2br($e->getTraceAsString());
} catch (\ValidationException $e) {
    /** this exception must not be catched here */
    $message  = 'ValidationException must have been catched before.';
    $message .= $e->getTraceAsString();
} catch (\Exception $e) {
    $message  = $e->getMessage();
} finally {
    if (isset($message)) {
        if ($is_production) {
            ob_end_clean();
            /**
             * @todo mail me
             */
            View::exit500();
        }
        exit($message);
    }
}