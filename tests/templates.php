<?php
/**
* Test functions for the templates
*
**/
require '../vendor/autoload.php';
use LightnCandy\LightnCandy;

const TEMPLATE_OPTIONS  = array(
		/** helpers **/
		'helpers' => array(
			/**
			* Allow to load subtemplates
			**/
			'load_subtemplate' => 'load_subtemplate',
			'load_partial'     => 'load_partial'
		),
		'flags' =>
			LightnCandy::FLAG_RENDER_DEBUG |
			LightnCandy::FLAG_HANDLEBARSJS |
			LightnCandy::FLAG_SPVARS
	);
const SUBTEMPLATE_OPTIONS = array(
		'helpers' => array(
				'load_partial' => 'load_partial'
			),
		'flags'  => \LightnCandy\LightnCandy::FLAG_SPVARS
	);
const PARTIAL_OPTIONS  = array(
		'helpers' => array(
		// allow calling partials from partials
			'load_partial' => 'load_partial'
		),
		'flags'  => \LightnCandy\LightnCandy::FLAG_SPVARS
	);

function load_template( $group, $strings = array(), $options = array() ) {
		$group_template = explode('/', $group);
		if( 2 !== count($group_template) ) {
			trigger_error(
					'Load template sintax must be "group/template"'
				);
			return;
		}
		$group         = $group_template[0];
		$template      = $group_template[1];
		$templates_dir = dirname(__FILE__) . '/templates/';
		/**
		* $templates_dir is the template for
		* all the dirs for templates
		* while $template_dir is only
		* for this group.
		**/
		$template_dir  = $templates_dir . $group . '/';
		if( ! is_dir($template_dir) ) {
			/**
			* The templates are separated in groups
			* The templates for the main site are in the dir
			* views/main
			* future templates will be in different dirs
			* like admin/main, support/main
			* etc.
			* If there's no dir, then we have nothing to look at there.
			**/
			trigger_error('Could not load templates dir ' . $templates_dir);
			return;
		}
		$template_file = $template_dir . $template . '.hbs';
		if( ! file_exists($template_file) ) {
			/**
			* if the template does not exist...
			**/
			trigger_error('Template does not exist: ' . $template);
			return;
		}
		/*!
		* WARNING: HERE BE DRAGONS
		* WARNING: HERE BE DRAGONS
		!*/
		/**
		* Get the default strings for the group
		* which are in a file. It contains
		* the header/footer strings
		* and other stuff.
		**/
		$default_strings  = get_default_strings($group);
		$strings          = array_merge($default_strings, $strings);
		/**
		* This is global info for the templates
		**/
		$GLOBALS['_TEMPLATE'] = array(
				'group'         => $group,
				'strings'       => $strings,
				'templates_dir' => $templates_dir
			);
		/**
		* Let's load our group template
		**/
		$template_php = LightnCandy::compile(
				file_get_contents($templates_dir . $group . '.hbs'),
				array_merge_recursive(
					TEMPLATE_OPTIONS,
					$options
				)
			);
		$renderer = LightnCandy::prepare($template_php);
		return $renderer(
			array(
				'subtemplate' => $template
			) + $strings
		);
}
/**
* This is a function for loading partials inside the subtemplates
* and the group templates. With this we can call templates
* inside the group/partials dir and pass arguments.
* That may be used to loop posts, users, audios, etc.
* This function is not called from PHP. It is called
* from a template. Like this:
* {{load_partial 'posts' post}}
* {{load_partial 'sidebar'}}
*
* @param $partial_name string  - The name of the file without extension
* @param $args (optional) array- An array with strings that
* the template needs.
* @return string
**/
function load_partial( $partial_name, $args = array(), $options = array() ) {
	global $_TEMPLATE;
	/**
	* If the partial was called INSIDE a loop
	* then take the pair key=>value
	**/
	if( is_array($args) && ! empty($args['_this']) ) {
		$args = $args['_this'];
	}
					// namespace is obligatory
	$template_php = \LightnCandy\LightnCandy::compile(
			/** partial to compile **/
			file_get_contents(
				sprintf(
					'%s/%s/partials/%s.hbs',
					$_TEMPLATE['templates_dir'],
					$_TEMPLATE['group'],
					$partial_name
				)
			),
			array_merge_recursive(
				PARTIAL_OPTIONS,
				$options
			)
		);
	$renderer = \LightnCandy\LightnCandy::prepare($template_php);
	// go ahead!
	return $renderer( $args );
}
/**
 * This function instead, loads a subtemplate
 * which is for an specific page.
 * It can NOT load another subtemplates
 * but it can load partials.
 * @param string $subtemplate_name The name of the sub template.
 * @param array  $args             Context passed from the template or if you're
 *                                 calling it directly.
 *                                 Ex:   {{load_subtemplate 'test' this}}
 *                                 Ex 2: load_subtemplate('test', array())
 * @param array $options           If you're calling it directly,
 *                                 you can pass options for the LightnCandy
 *                                 (like helpers or flags).
 * @return string
**/
function load_subtemplate( $subtemplate_name, $args    = array(),
											  $options = array()
											) {
	global $_TEMPLATE;
	$template_php = \LightnCandy\LightnCandy::compile(
			/** partial to compile **/
			file_get_contents(
				sprintf(
					'%s/%s/%s.hbs',
					// here comes my hack again
					$_TEMPLATE['templates_dir'],
					$_TEMPLATE['group'],
					$subtemplate_name
				)
			),
			array_merge_recursive(
				SUBTEMPLATE_OPTIONS,
				$options
			)
		);
	$renderer = \LightnCandy\LightnCandy::prepare($template_php);
	return $renderer($args);
}
/**
* This function returns an array
* with the default strings
* for the group. Those strings are
* the header, footer and globals.
* It should be a PHP file with an array
* called $strings
* @param $group string
* @return array
**/
function get_default_strings( $group ) {
	$file = dirname(__FILE__) . '/templates/' . $group . '.php';
	if( ! file_exists($file) ) {
		return array();
	}
	require $file;
	return $strings;
}

echo load_template('main/posts', array(
		'posts' => array(
				array(
					'title'   => 'lol',
					'content' => 'lol2'
 				),
 				array(
 					'title'   => 'xd',
 					'content' => 'xd2'
 				)
			),
		'text' => 'Im a text',
	)
);