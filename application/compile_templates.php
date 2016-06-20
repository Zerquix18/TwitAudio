<?php
/**
 * compile_templates.php
 * This file compiles all the templates of view/
 * So they can be used in production
 */

// make this file executable from anywhere
chdir( dirname(__FILE__) );
chdir('../');

require_once('./index.php');

use \LightnCandy\LightnCandy,
    \application\View;

$dir         = getcwd() . '/views/';
$destiny_dir = getcwd() . '/views_compiled/';

function _log($what)
{
    echo $what, PHP_EOL;
}

if (is_dir($destiny_dir)) {
    _log('Cleaning ' . $destiny_dir);
    // if it's already a dir, delete everything there
    // so if a template was deleted it will not remain
    $all_files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $destiny_dir,
                    RecursiveDirectoryIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );
    foreach ($all_files as $file_info) {
        $function_to_call = ($file_info->isDir() ? 'rmdir' : 'unlink');
        $function_to_call( $file_info->getRealPath() );
    }
} else {
    _log('Creating ' . $destiny_dir);
    // if it does not exist, then create it
    mkdir($destiny_dir);
}

// now we got an empty dir, let's fill it

$groups_dir       = scandir($dir);
// remove '.' and '..'
$groups_dir       = array_slice($groups_dir, 2);

// GO !
$groups_dir_count = count($groups_dir);
for ($i = 0; $i < $groups_dir_count; $i++) {

    /*** THE PREFIX d_ MEANS destiny, I'm lazy sorry ***/

    $group_name      = $groups_dir[$i];
    $group_dir       = $dir         . $group_name . '/';
    $d_group_dir     = $destiny_dir . $group_name . '/';
    mkdir($d_group_dir);

    _log('Preparing group: ' . $group_name);

    // move the default template bars
    $default_template_bars = $group_name . '-bars.php';
    copy(
        $group_dir   . $default_template_bars,
        $d_group_dir . $default_template_bars
    );

    $options         = View::getTemplateOptions();

    /*
        now, compile the default template of that group
        will pass from
        views/main/main.hbs => views-compiled/main/main.php
    */
    $template_file   = $group_dir   . $group_name . '.hbs';
    $d_template_file = $d_group_dir . $group_name . '.php';
    _log('Compiling default template file: ' . $template_file);

    $template_php    = LightnCandy::compile(
            file_get_contents($template_file),
            $options['group_options']
    );
    // save the PHP file:
    _log('Saving default template file: ' . $d_template_file);
    file_put_contents($d_template_file, '<?php ' . $template_php . '?>');

        
    // done with the default template of the group
    /***************************************************************/
    // now templates and partials of that group
    
    // inside this dir, there are to dirs: 'templates' and 'partials'
    // I don't know if more dirs may be added in the future,
    // so I loop them dinamically
    $group_templates_dir        = scandir($group_dir);
    // skip '.', and '..'
    $group_templates_dir        = array_slice($group_templates_dir, 2);
    $group_templates_dir_count  = count($group_templates_dir);

    // now loop both dirs:
    for ($n = 0; $n < $group_templates_dir_count; $n++) {

        // save for later comparison
        // it can't have the path
        $current_templates_dir = $group_templates_dir[$n];

        if (! is_dir($group_dir . $current_templates_dir)) {
            // inside here there is the main.php and main.hbs
            continue; 
        }

        // path to the dir, which may be 'partials' or 'templates'
        $template_dir        = $group_dir   . $current_templates_dir . '/';
        $d_template_dir      = $d_group_dir . $current_templates_dir . '/';
        mkdir($d_template_dir);
        // all the templates inside that dir
        $templates_dir       = scandir($template_dir);
        // remove '.' and '..'
        $templates_dir       = array_slice($templates_dir, 2);

        $templates_dir_count = count($templates_dir);


        _log('Going to compile the templates of the dir: ' . $template_dir);

        for ($j = 0; $j < $templates_dir_count; $j++) {

            // the path to the template to compile
            $template_file   = $templates_dir[$j];
            // replace .hbs for .php
            $d_template_file = substr($template_file, 0, -4) . '.php';
            // add the path before
            $template_file   = $template_dir   . $template_file;
            $d_template_file = $d_template_dir . $d_template_file;

            //check if it is partials or templates
            //to call the right options
            if ('templates' == $current_templates_dir) {
                $compile_options = $options['template_options'];
            } elseif ('partials' == $current_templates_dir) {
                $compile_options = $options['partial_options'];
            } else {
                /* @todo MAIL ME CUZ THIS CAN'T HAPPEN */
                exit('wow');
            }

            _log('Compiling: ' . $template_file);
            $template_php  = \LightnCandy\LightnCandy::compile(
                file_get_contents($template_file),
                $compile_options
            );
            // done! now save it :)

            _log('Saving: ' . $d_template_file);
            file_put_contents(
                $d_template_file,
                '<?php ' . $template_php . ' ?>'
            );
        }
    }
}