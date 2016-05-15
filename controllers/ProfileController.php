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
		// the user in the request to load the profile:
		$user         = Users::get($user);
		// the logged user (if there is one):
		$current_user = Users::get_current_user();
		if( ! $user ) {
			View::exit_404();
		}

		if( 'audios' == $profile_page ) {

			if( ! $current_user->can_listen($user['id']) ) {
				$content = false;
			} else {
				$content = Audios::get_audios($user['id'], 1);
			}

			$errors = array(
					'empty'		=> // ↓
					'This user has not uploaded audios... yet',
					'forbidden' => // ↓
					'The audios of this user are private. You must be following this user on Twitter to see his / her audios.',
				);

		} elseif( 'favorites' == $profile_page) {

				/** not public **/			 /** not logged **/
			if(    ! $user['favs_public']
				|| ! is_logged() 
				|| $current_user->id !== $user['id']
			) {
				$content = false;
			} else {
				$content = Audios::get_favorites($user['id'], 1);
			}

			$errors = array(
					'empty'		=> // ↓
					'This user has not uploaded audios... yet',
					'forbidden' => // ↓
					'The favorites of this user are private',
				);

		} else return; // must never happen

		if( false !== $content ) {
			$total_audios    = Audios::get_audios_count($user['id']);
			$total_favorites = Audios::get_favorites_count($user['id']);
		}else{
			$total_audios = $total_favorites = false;
		}

		$template = array(
				'user'		=> $user,
				'content'	=> $content,
				'errors'	=> $errors,
				'page'		=> $profile_page,
				'total_audios' => $total_audios,
				'total_favorites' => $total_favorites
			);

		View::load_full_template('profile', $template);
	}
}