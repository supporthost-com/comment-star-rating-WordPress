function ci_comment_rating_categories() {
	return array ('opinioni');
}

function ci_comment_rating_types() {
	return array('reliability' => 'Affidabilità', 'support' => 'Supporto', 'price' => 'Prezzo', 'features' => 'Features');
}

add_action( 'comment_form_logged_in_after', 'ci_comment_rating_rating_field' );
add_action( 'comment_form_after_fields', 'ci_comment_rating_rating_field' );
function ci_comment_rating_rating_field () {
	if ( in_category(ci_comment_rating_categories()) ) {
		?>
		<label for="rating">Rating<span class="required">*</span></label>
		<fieldset class="comments-rating">

				<?php
				$types = ci_comment_rating_types();
				foreach ( $types as $key => $value) :
				?>
	<span class="rating-container">
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<input type="radio" id="<?php echo $key; ?>-<?php echo esc_attr( $i ); ?>" name="<?php echo $key; ?>" value="<?php echo esc_attr( $i ); ?>" /><label for="<?php echo $key; ?>-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></label>
					<?php endfor; ?>
					<input type="radio" id="<?php echo $key; ?>-0" class="star-cb-clear" name="<?php echo $key; ?>" value="0" /><label for="<?php echo $key; ?>-0">0</label>
		<div class="rating-label"><?php echo $value; ?></div>
	</span>
				<?php endforeach; ?>

		</fieldset>
		<?php
	}
}

//Save the rating submitted by the user.
add_action( 'comment_post', 'ci_comment_rating_save_comment_rating' );
function ci_comment_rating_save_comment_rating( $comment_id ) {
	$types = ci_comment_rating_types();
	foreach ( $types as $key => $value) {
		if ( ( isset( $_POST[$key] ) ) && ( '' !== $_POST[$key] ) )
		$rating = intval( $_POST[$key] );
		add_comment_meta( $comment_id, $key, $rating );
	}
}

//Make the rating required.
add_filter( 'preprocess_comment', 'ci_comment_rating_require_rating' );
function ci_comment_rating_require_rating( $commentdata ) {
	if ( in_category(ci_comment_rating_categories(), $_POST['comment_post_ID']) ) {
		$types = ci_comment_rating_types();
		foreach ( $types as $key => $value) {
			if ( ! is_admin() && ( ! isset( $_POST[$key] ) || 0 === intval( $_POST[$key] ) ) ) {
				wp_die( __( 'Errore: Seleziona un voto per ogni sezione. Torna indietro nel browser e seleziona un voto per ogni sezione..' ) );
			}
		}
	}
	return $commentdata;
}

//Display the rating on a submitted comment.
add_filter( 'comment_text', 'ci_comment_rating_display_rating');
function ci_comment_rating_display_rating( $comment_text ){
	$types = ci_comment_rating_types();
	foreach ( $types as $key => $value) {
		if ( $rating = get_comment_meta( get_comment_ID(), $key, true ) ) {
			$stars = '<div class="stars"><span class="show-comment-label">'.$value.'</span><span class="show-stars">';
			for ( $i = 1; $i <= $rating; $i++ ) {
				$stars .= '<span class="dashicons dashicons-star-filled"></span>';
			}
			$stars .= '</span></div>';
			$comment_stars .= $stars;
		}
	}
	return $comment_text . '<div class="stars-container">' . $comment_stars . '</div>';
}







//Get the average rating of a post.
function ci_comment_rating_get_average_ratings( $id, $key ) {
	$comments = get_approved_comments( $id );

	if ( $comments ) {
		$i = 0;
		$total = 0;
		foreach( $comments as $comment ){
			$rate = get_comment_meta( $comment->comment_ID, $key, true );
			if( isset( $rate ) && '' !== $rate ) {
				$i++;
				$total += $rate;
			}
		}

		if ( 0 === $i ) {
			return false;
		} else {
			return round( $total / $i, 1 );
		}
	} else {
		return false;
	}
}

//Display the average rating above the content.
add_filter( 'the_content', 'ci_comment_rating_display_average_rating' );
function ci_comment_rating_display_average_rating( $content ) {

	global $post;

	$types = ci_comment_rating_types();
	foreach ( $types as $key => $value) {
		if ( false === ci_comment_rating_get_average_ratings( $post->ID, $key ) ) {
			return $content;
		}
	}
	
	$types = ci_comment_rating_types();
	foreach ( $types as $key => $value) {
		$stars[$key]   = '';
		$average[$key] = ci_comment_rating_get_average_ratings( $post->ID, $key );

		for ( $i = 1; $i <= $average[$key] + 1; $i++ ) {

			$width[$key] = intval( $i - $average[$key] > 0 ? 20 - ( ( $i - $average[$key] ) * 20 ) : 20 );

			if ( 0 === $width[$key] ) {
				continue;
			}

			$stars[$key] .= '<span style="overflow:hidden; width:' . $width[$key] . 'px" class="dashicons dashicons-star-filled"></span>';

			if ( $i - $average[$key] > 0 ) {
				$stars[$key] .= '<span style="overflow:hidden; position:relative; left:-' . $width[$key] .'px;" class="dashicons dashicons-star-empty"></span>';
			}
		}
	}
	$types = ci_comment_rating_types();
	$custom_content  = '<div class="average-rating-box">';
	foreach ( $types as $key => $value) {
		$custom_content  .= '<div class="average-rating-single">' . $value . ' <span class="average-rating-value">' . $average[$key] .'</span> <span class="average-rating-stars">' . $stars[$key] .'</span></div>';
	}
	$custom_content .= '</div>' . $content;
	if ( is_single() ) {
		return $custom_content;
	} else {
		return $content;
	}
}
