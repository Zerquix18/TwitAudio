<?php
/**
*
* Le profile controller
*
* Manages everything related to profiles
*
* @author Zerquix18 <zerquix18@outlook.com>
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/
namespace controllers;
use \application\View,
	\models\Audios,
	\models\Users;

class ProfileController {

	public function __construct( $profile_page, $user ) {
		try {
			// the user in the request to load the profile:
			$user         = Users::get($user);
			// the logged user (if there is one):
			$current_user = Users::get_current_user();
			if( ! $user ) {
				// user does not exist
				View::exit_404();
			}

			if( 'audios' == $profile_page ) {

				if( $current_user->can_listen($user['id']) ) {
					$audios       = Audios::get_audios($user['id'], 1);
					$is_forbidden = false;
				} else {
					$audios       = array();
					$is_forbidden = true;
				}

				$errors = array(
						'empty'		=> // ↓
						'This user has not uploaded audios... yet.',
						'forbidden' => // ↓
						'The audios of this user are private. ' .
						'You must be following this user on ' .
						'Twitter to see his / her audios.',
					);

			} elseif( 'favorites' == $profile_page ) {

				if(    'public' == $user['favs_privacy']
					|| (
						is_logged() && ($current_user->id == $user['id'])
					)
				) {
					// the favs must me public or it has to be the same user
					$audios       = Audios::get_favorites($user['id'], 1);
					$is_forbidden = false;
				} else {
					$audios       = array();
					$is_forbidden = true;
				}

				$errors = array(
						'empty'		=> // ↓
						'This user has not favorited audios... yet',
						'forbidden' => // ↓
						'The favorites of this user are private',
					);

			} else return; // can it ever happen?

			if( $audios ) {
				// in the first page we only show the first 10
				// this is the total
				$total_audios    = Audios::get_audios_count($user['id']);
				$total_favorites = Audios::get_favorites_count($user['id']);
			} else {
				$total_audios    =
				$total_favorites = 0;
			}

			$bars = array(
				'profile' => array(
					'user'            => $user,
					'audios'          => $audios,
					'errors'          => $errors,
					'page'            => $profile_page,
					'total_audios'    => $total_audios,
					'total_favorites' => $total_favorites,
					'is_forbidden'    => $is_forbidden,
					'is_audios'       => 'audios'    == $profile_page,
					'is_favorites'    => 'favorites' == $profile_page,
				)
			);

			if( 'audios' === $profile_page ) {
				$title = sprintf('%s audios',    $user['username']);
			} else {
				$title = sprintf('%s favorites', $user['username']);
			}
			View::set_title($title);
			View::set_page('profile');
			View::set_robots( !! $audios );
			echo View::get_group_template('main/profile', $bars);
		} catch ( \Exception $e ) {
			// database error or template error :c
			if( \Config::get('is_production') ) {
				View::exit_500();
			} else {
				echo $e->getMessage(), PHP_EOL;
			}//if
		}//catch
	}//__construct
}//class