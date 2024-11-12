<?php
namespace Sellkit\Compatibility;

use Sellkit\Compatibility\Wpml;

defined( 'ABSPATH' ) || die();

/**
 * Sellkit compatibility module.
 *
 * Sellkit compatibility module handler class is responsible for registering and
 * managing 3rd-party compatibility with Sellkit.
 *
 * @since 2.3.2
 */
class Module {

	/**
	 * Constructor.
	 *
	 * @since 2.3.2
	 */
	public function __construct() {
		// Instantiate compatibility modules.
		new Wpml\Module();
	}
}

new Module();
