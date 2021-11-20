<footer class="footer <?php echo esc_attr( get_theme_mod( 'footer_reveal', 1 ) ? 'footer-revealing' : '' ); ?>">

			<?php if ( is_active_sidebar( 'footer-1' )
				|| is_active_sidebar( 'footer-2' )
				|| is_active_sidebar( 'footer-3' )
				|| is_active_sidebar( 'footer-4' ) ) :
			?>
				<div class="footer-widgets">
					<div class="container">
						<div class="row">
							<div class="col-lg-3 col-md-6 col-xs-12">
								<?php dynamic_sidebar( 'footer-1' ); ?>
							</div>
							<div class="col-lg-3 col-md-6 col-xs-12">
								<?php dynamic_sidebar( 'footer-2' ); ?>
							</div>
							<div class="col-lg-3 col-md-6 col-xs-12">
								<?php dynamic_sidebar( 'footer-3' ); ?>
							</div>
							<div class="col-lg-3 col-md-6 col-xs-12">
								<?php dynamic_sidebar( 'footer-4' ); ?>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="footer-wrap">
				<div class="footer-fixed">
					<div class="foot">
						<div class="container">
							<div class="row row-table">
								<div class="col-md-7 col-xs-12">
									<?php wp_nav_menu( array(
										'theme_location' => 'footer_menu',
										'container'      => '',
										'menu_id'        => '',
										'menu_class'     => 'nav-list-inline',
										'fallback_cb'    => false,
										'depth'          => 1,
									) ); ?>
								</div>

								<?php if ( get_theme_mod( 'footer_cards' ) ) : ?>
									<div class="col-md-5 col-xs-12 footer-cards">
										<?php $image_info = wp_prepare_attachment_for_js( get_theme_mod( 'footer_cards' ) ); ?>
										<img src="<?php echo esc_url( neto_get_image_src( get_theme_mod( 'footer_cards' ), 'full' ) ); ?>" alt="<?php echo esc_attr( $image_info['alt'] ); ?>"/>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<?php if ( is_active_sidebar( 'footer-instagram' ) ) : ?>
						<div class="footer-instagram" data-auto="<?php echo esc_attr( get_theme_mod( 'instagram_auto', 1 ) ); ?>" data-speed="<?php echo esc_attr( get_theme_mod( 'instagram_speed', 300 ) ); ?>">
							<?php dynamic_sidebar( 'footer-instagram' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</footer>