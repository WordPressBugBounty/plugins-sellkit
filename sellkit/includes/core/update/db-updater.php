<?php

namespace Sellkit\Core\Update;

use Sellkit\Database;

defined( 'ABSPATH' ) || die();

/**
 * Class Data base Updater.
 *
 * @package Sellkit\Contact_Segmentation
 * @SuppressWarnings(ExcessiveClassComplexity)
 * @since 1.1.0
 */
class Db_Updater extends \WP_Background_Process {

	/**
	 * Whether this site already has migration batches queued or in progress.
	 *
	 * @since 2.4.1
	 * @return bool
	 */
	public function has_pending_migrations() {
		return ! $this->is_queue_empty();
	}

	/**
	 * SellKit db version.
	 *
	 * @since 1.1.0
	 * @var $db_version
	 */
	public $db_version;

	/**
	 * Queue Action.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $action = 'sellkit-database-updater';

	/**
	 * When set before the first save()->dispatch() from Install, multisite uses a short network-wide
	 * cooldown so not every subsite fires a loopback HTTP request at the same time after an update.
	 * Chained dispatch() calls from the background handler leave this false and are not gated.
	 *
	 * @var bool
	 */
	public $gate_multisite_initial_loopback = false;

	/**
	 * Network-wide cooldown in seconds for the initial multisite loopback spawn.
	 *
	 * @var int
	 */
	protected $initial_loopback_gate_ttl = MINUTE_IN_SECONDS;

	/**
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 1.1.0
	 * @param mixed $data Queue item to iterate over.
	 */
	protected function task( $data ) {
		if ( empty( $data['callback_function'] ) || empty( $data['db_version'] ) ) {
			return false;
		}

		$this->db_version  = $data['db_version'];
		$updater_functions = new Updater_Functions();

		call_user_func( [ $updater_functions, $data['callback_function'] ] );

		return false;
	}

	/**
	 * Spawn loopback to process the queue; on multisite optionally throttle initial storm across subsites.
	 *
	 * @since 2.4.1
	 * @return array|false|WP_Error
	 */
	public function dispatch() {
		if ( $this->gate_multisite_initial_loopback && is_multisite() ) {
			$this->gate_multisite_initial_loopback = false;

			// Allow one initial loopback at a time network-wide; other sites rely on cron until the cooldown expires.
			if ( ! $this->acquire_multisite_initial_loopback_gate() ) {
				$this->schedule_event();
				return false;
			}
		}

		return parent::dispatch();
	}

	/**
	 * Acquire the multisite loopback gate using the main site's options table.
	 *
	 * `add_option()` is backed by a unique index in `wp_options`, which makes it much safer than a
	 * get/set transient pair during concurrent requests across many subsites.
	 *
	 * @return bool
	 */
	protected function acquire_multisite_initial_loopback_gate() {
		$network_key  = 'sellkit_ms_init_loopback_gate';
		$expires_at   = time() + $this->initial_loopback_gate_ttl;
		$main_site_id = (int) get_main_site_id();
		$switched     = false;

		if ( $main_site_id > 0 && get_current_blog_id() !== $main_site_id ) {
			switch_to_blog( $main_site_id );
			$switched = true;
		}

		$acquired = add_option( $network_key, $expires_at, '', false );

		if ( ! $acquired ) {
			$existing_expiry = (int) get_option( $network_key );

			if ( $existing_expiry <= time() ) {
				delete_option( $network_key );
				$acquired = add_option( $network_key, $expires_at, '', false );
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}

		return $acquired;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @since 1.1.0
	 */
	protected function complete() {
		parent::complete();

		sellkit_update_option( 'current_db_version', $this->db_version );
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 *
	 * @since 2.3.3
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing.
		session_write_close();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );

			exit;
		}

		if ( $this->is_process_running() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
	}
}
