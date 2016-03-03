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

use \application\Views;
use \models\Audio;
use \models\User;

class ProfileController {

	public function __construct( $profile_page, $user ) {
		$users_model = new User();
		$user = $users_model->get_user_info( $user );
		if( ! $user )
			Views::exit_404();

		$audios_model = new Audio;

		if( 'audios' == $profile_page ) {

			if( ! $users_model->can_listen( $user->id ) )
				$content = false;
			else
				$content = $audios_model->load_audios( $user->id, 1 );

			$errors = array(
					'empty'		=> // ↓
					__('This user has not uploaded audios... yet'),
					'forbidden' => // ↓
					__('The audios of this user are private. You must be following this user on Twitter to see his / her audios.'),
				);

		} elseif( 'favorites' == $profile_page) {

				/** not public **/			 /** not logged **/
			if( 0 == $user->favs_public && ( null == $users_model->user 
					/* logged... but not the same user */
				|| $users_model->user->id !== $user->id ) )
				$content = false;
			else
				$content = $audios_model->load_favorites( $user->id, 1);

			$errors = array(
					'empty'		=> // ↓
					__('This user has not uploaded audios... yet'),
					'forbidden' => // ↓
					__('The favorites of this user are private'),
				);

		} else return; // must never happen

		if( false !== $content ) {
			$total_audios    = $users_model->get_audios_count( $user->id );
			$total_favorites = $users_model->get_favorites_count( $user->id );
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

		Views::load_full_template('profile', $template);
	}
}