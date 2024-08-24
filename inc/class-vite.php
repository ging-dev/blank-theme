<?php

/**
 * @see https://vitejs.dev/guide/backend-integration
 *
 * @phpstan-type ChunkType array{
 *      file: string,
 *      src: string,
 *      isEntry?: bool,
 *      imports?: string[],
 *      css?: string[],
 *      name?: string,
 *      isDynamicEntry?: bool,
 *      dynamicImports?: string[],
 * }
 */
class Vite {
	private string $base_uri;

	public bool $is_running_hot;

	/** @var array<string, ChunkType> */
	private array $manifest = array();

	public function __construct( string $build_dir = 'build' ) {
		$this->is_running_hot = file_exists( $hot = get_template_directory() . '/hot' );

		$this->base_uri = $this->is_running_hot
			? rtrim( file_get_contents( $hot ) )
			: get_template_directory_uri() . "/{$build_dir}";

		if ( $this->is_running_hot ) {
			wp_enqueue_script_module( '@vite/client', "{$this->base_uri}/@vite/client", array(), null );

			return;
		}

		$this->manifest = file_exists(
			$manifest   = get_template_directory() . "/{$build_dir}/manifest.json"
		) ? json_decode( file_get_contents( $manifest ), true ) : throw new RuntimeException(
			sprintf( 'File "%s" does not exist.', $manifest )
		);
	}

	public function inject( string $entry ): void {
		$this->resolve( $entry );
	}

	private function resolve( string $entry, bool $throw = true ): string {
		$entry = ltrim( $entry, '/' );

		/** @var ChunkType */
		$chunk = ( $this->is_running_hot || ! $throw ) ? array(
			'src'  => $entry,
			'file' => $entry,
		) : $this->manifest[ $entry ] ?? throw new RuntimeException(
			sprintf( 'Entry "%s" does not exist.', $entry )
		);

		foreach ( $chunk['css'] ?? array() as $css ) {
			$this->resolve( $css, false );
		}

		$deps = array();
		foreach ( $chunk['imports'] ?? array() as $import ) {
			$deps[] = array(
				'id'     => $this->resolve( $import ),
				'import' => 'static',
			);
		}

		foreach ( $chunk['dynamicImports'] ?? array() as $dynamicImport ) {
			$deps[] = array(
				'id'     => $this->resolve( $dynamicImport ),
				'import' => 'dynamic',
			);
		}

		$handle = pathinfo( $chunk['file'], PATHINFO_FILENAME );
		$src    = "{$this->base_uri}/{$chunk['file']}";

		if ( $this->is_stypesheet( $src ) ) {
			wp_enqueue_style( $handle, $src );
		} elseif ( $chunk['isEntry'] ?? false ) {
			wp_enqueue_script_module( $handle, $src, $deps );
		} else {
			// mark as preload
			wp_register_script_module( $handle, $src, $deps );
		}

		return $handle;
	}

	private function is_stypesheet( string $path ): bool {
		return preg_match( '/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path ) === 1;
	}
}
