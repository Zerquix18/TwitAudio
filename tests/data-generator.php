<?php
/**
* generator for data to be inserted in a test database
* generates an SQL file
*
**/
$file_name = 'fill-db.sql';
chdir( dirname(__FILE__) );
chdir( '../' );
require_once('./index.php');
// clean the HTML:
ob_flush();
ob_end_clean();
// now work:

for($i = 0; $i < 100; $i++) {
	/** FILL audios ! **/
	$db->insert("audios", array(
			$audio_id = generate_id_for('audio'),
			"142105347", // my id (Zerquix18)
			'noexistingfile.mp3', // nameofthefile.mp3
			0, // reply_to
			"Audio #" . $i,
			0, // twitter id
			time(),
			0, // plays
			0, // favorites
			"12",
			"0"
		)
	) or die($db->error);
	for($j = 0; $j < 50; $j++) {
		/** FILL replies! **/
		$db->insert("audios", array(
				$reply_id = generate_id_for('audio'),
				"142105347",
				'', // audio.mp3 (not used here)
				$audio_id, // reply_to
				"Reply #" . $j,
				0,
				time(),
				0,
				0,
				0,
				'0' // is_voice (the answer is no)
			)
		) or die($db->error);
	}
}