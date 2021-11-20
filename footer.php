	<?php 

	$lookbook = false;

	if ( $sm_site_settings = get_page_by_path( 'site-settings', OBJECT, 'page' ) ){
		
		$id = $sm_site_settings->ID; 
		$modes = get_field('modes', $id);
		$lookbook = $modes && (array_search('lookbook', $modes) != false);
	}


	if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) : ?>

		<?php do_action( 'neto_before_footer' ); 

		if($lookbook) {
			echo 
			"<footer class='footer" . esc_attr( get_theme_mod( 'footer_reveal', 1 ) ? 'footer-revealing' : '' ) . "'>
			</footer>";
		} else {
			include 'standard_footer.php';
		}
		?>

		<?php do_action( 'neto_after_footer' ); ?>

	<?php endif; ?>

</div> <!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
