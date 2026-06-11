<?php
/**
 * WP-CLI execution abilities.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\Auth;
use LayrShift\Sandbox;

final class RunWpCli {

	use AbilityTrait;

	public static function register(): void {
		if ( ! wp_has_ability( 'layrshift/run-wp-cli' ) ) {
			wp_register_ability(
				'layrshift/run-wp-cli',
				array(
					'label'               => __( 'Run WP-CLI Command', 'layrshift' ),
					'description'         => __( 'Runs a WP-CLI command on the server synchronously or in the background.', 'layrshift' ),
					'category'            => 'code-execution',
					'execute_callback'    => array( self::class, 'run' ),
					'permission_callback' => array( Auth::class, 'check_ability_permission' ),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'args'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'minItems' => 1 ),
							'async' => array( 'type' => 'boolean', 'default' => false ),
						),
						'required'   => array( 'args' ),
					),
					'meta'                => array(
						'mcp'         => array( 'public' => true, 'type' => 'tool' ),
						'annotations' => array( 'destructive' => true ),
					),
				)
			);
		}

		if ( ! wp_has_ability( 'layrshift/get-wp-cli-job' ) ) {
			wp_register_ability(
				'layrshift/get-wp-cli-job',
				array(
					'label'               => __( 'Get WP-CLI Job Status', 'layrshift' ),
					'description'         => __( 'Checks the status of an asynchronous background WP-CLI job.', 'layrshift' ),
					'category'            => 'code-execution',
					'execute_callback'    => array( self::class, 'get_job' ),
					'permission_callback' => array( Auth::class, 'check_ability_permission' ),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'job_id' => array( 'type' => 'string', 'minLength' => 1 ),
							'offset' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
							'limit'  => array( 'type' => 'integer', 'default' => 1048576 ),
						),
						'required'   => array( 'job_id' ),
					),
					'meta'                => array(
						'mcp'         => array( 'public' => true, 'type' => 'tool' ),
						'annotations' => array( 'readOnly' => true ),
					),
				)
			);
		}
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function run( array $input ) {
		return self::run_logged( 'layrshift/run-wp-cli', $input, static function () use ( $input ) {
			$raw_args = is_array( $input['args'] ?? null ) ? $input['args'] : array();
			$args     = array();
			foreach ( $raw_args as $arg ) {
				if ( ! is_string( $arg ) ) {
					return new \WP_Error( 'invalid_wp_cli_arg', __( 'WP-CLI arguments must be strings.', 'layrshift' ) );
				}
				$args[] = $arg;
			}

			if ( ! function_exists( 'proc_open' ) || ! function_exists( 'exec' ) ) {
				return new \WP_Error( 'process_execution_disabled', __( 'Process execution is disabled in PHP configuration.', 'layrshift' ) );
			}

			$wp_path = self::find_wp_cli_path();
			if ( null === $wp_path ) {
				return new \WP_Error( 'wp_cli_not_found', __( 'WP-CLI is not installed or not executable on this server.', 'layrshift' ) );
			}

			if ( self::is_current_user_root() && ! in_array( '--allow-root', $args, true ) ) {
				array_unshift( $args, '--allow-root' );
			}

			return ( true === ( $input['async'] ?? null ) )
				? self::run_async( $wp_path, $args )
				: self::run_sync( $wp_path, $args );
		} );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_job( array $input ) {
		return self::run_logged( 'layrshift/get-wp-cli-job', $input, static function () use ( $input ) {
			$job_id = (string) ( $input['job_id'] ?? '' );
			if ( 1 !== preg_match( '/^[a-f0-9]{16}$/i', $job_id ) ) {
				return new \WP_Error( 'invalid_job_id', __( 'Invalid job ID format.', 'layrshift' ) );
			}

			$job_dir     = self::job_dir();
			$log_file    = $job_dir . 'job_' . $job_id . '.log';
			$status_file = $job_dir . 'job_' . $job_id . '.status';

			if ( ! is_file( $log_file ) ) {
				return self::missing_job_result( $job_id, $status_file );
			}

			$status    = 'running';
			$exit_code = null;
			if ( is_file( $status_file ) ) {
				$status       = 'completed';
				$exit_content = trim( (string) file_get_contents( $status_file ) );
				if ( '' !== $exit_content ) {
					$exit_code = (int) $exit_content;
				}
			}

			$slice = self::read_log_slice( $log_file, (int) ( $input['offset'] ?? 0 ), (int) ( $input['limit'] ?? 1048576 ) );
			if ( is_wp_error( $slice ) ) {
				return $slice;
			}

			$result = array(
				'success'    => true,
				'job_id'     => $job_id,
				'status'     => $status,
				'stdout'     => $slice['content'],
				'bytes_read' => $slice['bytes_read'],
				'truncated'  => $slice['truncated'],
			);
			if ( null !== $exit_code ) {
				$result['exit_code'] = $exit_code;
			}

			return $result;
		} );
	}

	private static function job_dir(): string {
		$dir = trailingslashit( Sandbox::get_directory() ) . 'wp-cli-jobs/';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function missing_job_result( string $job_id, string $status_file ): array {
		if ( ! is_file( $status_file ) ) {
			return array(
				'success'    => false,
				'job_id'     => $job_id,
				'status'     => 'not_found',
				'stdout'     => '',
				'bytes_read' => 0,
				'truncated'  => false,
			);
		}

		$exit_content = trim( (string) file_get_contents( $status_file ) );
		$result       = array(
			'success'    => true,
			'job_id'     => $job_id,
			'status'     => 'completed',
			'stdout'     => '',
			'bytes_read' => 0,
			'truncated'  => false,
		);
		if ( '' !== $exit_content ) {
			$result['exit_code'] = (int) $exit_content;
		}
		return $result;
	}

	/**
	 * @return array{content: string, bytes_read: int, truncated: bool}|\WP_Error
	 */
	private static function read_log_slice( string $log_file, int $offset, int $limit ) {
		$size = (int) filesize( $log_file );
		if ( $offset >= $size ) {
			return array( 'content' => '', 'bytes_read' => 0, 'truncated' => false );
		}

		$handle = fopen( $log_file, 'rb' );
		if ( false === $handle ) {
			return new \WP_Error( 'read_failed', sprintf( __( 'Could not open log file: %s', 'layrshift' ), $log_file ) );
		}

		if ( $offset > 0 ) {
			fseek( $handle, $offset );
		}

		$read_length = -1 === $limit ? $size - $offset : $limit;
		$content     = fread( $handle, max( 1, $read_length ) );
		fclose( $handle );

		if ( false === $content ) {
			return new \WP_Error( 'read_failed', sprintf( __( 'Could not read log file: %s', 'layrshift' ), $log_file ) );
		}

		$bytes_read = strlen( $content );
		$truncated  = -1 !== $limit && ( $offset + $bytes_read ) < $size;

		return array(
			'content'    => $content,
			'bytes_read' => $bytes_read,
			'truncated'  => $truncated,
		);
	}

	private static function find_wp_cli_path(): ?string {
		if ( function_exists( 'exec' ) ) {
			foreach ( array( 'which wp 2>/dev/null', 'command -v wp 2>/dev/null' ) as $cmd ) {
				$output = array();
				$rc     = 0;
				exec( $cmd, $output, $rc );
				if ( 0 === $rc && isset( $output[0] ) && is_string( $output[0] ) && '' !== trim( $output[0] ) ) {
					return trim( $output[0] );
				}
			}
		}

		foreach ( array( '/usr/local/bin/wp', '/usr/bin/wp', '/bin/wp' ) as $path ) {
			if ( is_file( $path ) && is_executable( $path ) ) {
				return $path;
			}
		}

		return null;
	}

	private static function is_current_user_root(): bool {
		if ( function_exists( 'posix_getuid' ) && 0 === posix_getuid() ) {
			return true;
		}
		if ( 'root' === getenv( 'USER' ) || 'root' === getenv( 'USERNAME' ) ) {
			return true;
		}
		if ( function_exists( 'exec' ) ) {
			$whoami = array();
			$rc     = 0;
			exec( 'whoami 2>/dev/null', $whoami, $rc );
			if ( 0 === $rc && isset( $whoami[0] ) && 'root' === trim( (string) $whoami[0] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param list<string> $args
	 * @return array<string, mixed>
	 */
	private static function run_sync( string $wp_path, array $args ): array {
		$cmd            = array_merge( array( $wp_path ), $args );
		$descriptorspec = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$pipes   = array();
		$process = proc_open( $cmd, $descriptorspec, $pipes, ABSPATH );
		if ( ! is_resource( $process ) ) {
			return array(
				'success'   => false,
				'exit_code' => -1,
				'stdout'    => '',
				'stderr'    => __( 'Failed to start process.', 'layrshift' ),
			);
		}

		if ( isset( $pipes[0] ) && is_resource( $pipes[0] ) ) {
			fclose( $pipes[0] );
		}

		list( $stdout, $stderr ) = self::collect_process_output( $pipes[1] ?? null, $pipes[2] ?? null );
		$exit_code               = proc_close( $process );

		return array(
			'success'   => 0 === $exit_code,
			'exit_code' => $exit_code,
			'stdout'    => $stdout,
			'stderr'    => $stderr,
		);
	}

	/**
	 * @param list<string> $args
	 * @return array<string, mixed>
	 */
	private static function run_async( string $wp_path, array $args ): array {
		$job_id      = bin2hex( random_bytes( 8 ) );
		$job_dir     = self::job_dir();
		$log_file    = $job_dir . 'job_' . $job_id . '.log';
		$status_file = $job_dir . 'job_' . $job_id . '.status';

		if ( false === file_put_contents( $log_file, '' ) ) {
			return array(
				'success' => false,
				'stderr'  => sprintf( __( 'Failed to create log file: %s', 'layrshift' ), $log_file ),
			);
		}

		$wp_cmd           = escapeshellarg( $wp_path ) . ' ' . implode( ' ', array_map( 'escapeshellarg', $args ) );
		$background_cmd   = sprintf(
			'cd %s && (%s > %s 2>&1; echo $? > %s)',
			escapeshellarg( ABSPATH ),
			$wp_cmd,
			escapeshellarg( $log_file ),
			escapeshellarg( $status_file )
		);
		$cmd              = 'nohup sh -c ' . escapeshellarg( $background_cmd ) . ' > /dev/null 2>&1 & echo $!';
		$pid_output       = array();
		$rc               = 0;
		exec( $cmd, $pid_output, $rc );

		$pid = null;
		if ( 0 === $rc && isset( $pid_output[0] ) && '' !== trim( (string) $pid_output[0] ) ) {
			$pid = (int) trim( (string) $pid_output[0] );
		}

		if ( 0 !== $rc || null === $pid ) {
			file_put_contents( $status_file, '127' );
			return array(
				'success' => false,
				'job_id'  => $job_id,
				'stderr'  => __( 'Failed to start background WP-CLI process.', 'layrshift' ),
			);
		}

		return array(
			'success' => true,
			'job_id'  => $job_id,
			'pid'     => $pid,
		);
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	private static function collect_process_output( mixed $stdout_pipe, mixed $stderr_pipe ): array {
		if ( is_resource( $stdout_pipe ) ) {
			stream_set_blocking( $stdout_pipe, false );
		}
		if ( is_resource( $stderr_pipe ) ) {
			stream_set_blocking( $stderr_pipe, false );
		}

		$stdout = '';
		$stderr = '';

		while ( is_resource( $stdout_pipe ) || is_resource( $stderr_pipe ) ) {
			$read = array();
			if ( is_resource( $stdout_pipe ) ) {
				if ( feof( $stdout_pipe ) ) {
					fclose( $stdout_pipe );
					$stdout_pipe = null;
				} elseif ( is_resource( $stdout_pipe ) ) {
					$read[] = $stdout_pipe;
				}
			}
			if ( is_resource( $stderr_pipe ) ) {
				if ( feof( $stderr_pipe ) ) {
					fclose( $stderr_pipe );
					$stderr_pipe = null;
				} elseif ( is_resource( $stderr_pipe ) ) {
					$read[] = $stderr_pipe;
				}
			}

			if ( array() === $read ) {
				break;
			}

			$write  = null;
			$except = null;
			$ready  = stream_select( $read, $write, $except, 0, 200000 );
			if ( false === $ready || 0 === $ready ) {
				if ( false === $ready ) {
					break;
				}
				continue;
			}

			foreach ( $read as $pipe ) {
				$chunk = stream_get_contents( $pipe );
				if ( false === $chunk || '' === $chunk ) {
					continue;
				}
				if ( is_resource( $stdout_pipe ) && $pipe === $stdout_pipe ) {
					$stdout .= $chunk;
				} elseif ( is_resource( $stderr_pipe ) && $pipe === $stderr_pipe ) {
					$stderr .= $chunk;
				}
			}
		}

		return array( $stdout, $stderr );
	}
}
