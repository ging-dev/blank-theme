<?php

/**
 * @see https://github.com/Automattic/jetpack-autoloader
 */
require plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';
require plugin_dir_path( __FILE__ ) . '/inc/class-vite.php';

function theme_setup(): void {
	add_theme_support( 'title-tag' );
}

add_action( 'after_setup_theme', 'theme_setup' );
add_action(
	'wp_enqueue_scripts',
	function (): void {
		$vite = new Vite();
		$vite->inject( 'style.css' );
		$vite->inject( 'resources/app.js' );
	}
);
