<?php
namespace Sellkit\Blocks;

defined( 'ABSPATH' ) || die();

/**
 * SellKit Blocks class.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @since 2.3.0
 */
class Sellkit_Blocks {

	/**
	 * Class instance.
	 *
	 * @since 2.3.0
	 * @var Sellkit_Blocks
	 */
	private static $instance = null;

	/**
	 * Blocks.
	 *
	 * @since 2.3.0
	 * @var array
	 */
	public $blocks = [];

	/**
	 * Inner Blocks.
	 *
	 * @since 2.3.0
	 * @var array
	 */
	public $inner_blocks = [];

	/**
	 * Valid Blocks.
	 *
	 * @since 2.3.0
	 * @var array
	 */
	public $valid_blocks = [];

	/**
	 * Current post ID.
	 *
	 * @since 2.3.0
	 * @var null|integer
	 */
	public $post_id = null;

	/**
	 * Date localization.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $date_localization = '';

	/**
	 * Get a class instance.
	 *
	 * @since 2.3.0
	 *
	 * @return Sellkit_Blocks Class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'load_block_editor' ] );
		add_action( 'template_redirect', [ $this, 'load_block_frontend' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'register_blocks' ] );
		add_filter( 'block_categories_all', [ $this, 'add_sellkit_category' ] );

		$this->register_helpers();
	}

	/**
	 * Register blocks helper class.
	 *
	 * @since 2.3.0
	 */
	private function register_helpers() {
		$blocks = $this->get_blocks();

		if ( empty( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block ) {
			$block_data = explode( '/', $block );
			$block_name = $block_data[1];

			$class_path = 'block-editor/' . $block . '/helper';

			sellkit()->load_files( [
				$class_path,
			] );

			$class_name = str_replace( '-', ' ', $block_name );
			$class_name = str_replace( ' ', '_', ucwords( $class_name ) );
			$class_name = "Sellkit\Blocks\Helpers\\{$class_name}\\Helper";

			if ( class_exists( $class_name ) ) {
				new $class_name();
			}
		}
	}

	/**
	 * Add sellkit block category.
	 *
	 * @param array $categories Blocks categoris list.
	 * @since 2.3.0
	 */
	public function add_sellkit_category( $categories ) {
		return array_merge(
			$categories,
			[
				[
					'slug'  => 'sellkit-blocks',
					'title' => esc_html__( 'sellkit', 'sellkit' ),
					'icon'  => '',
				],
			]
		);
	}

	/**
	 * Get sellkit valid block list.
	 *
	 * @since 2.3.0
	 * @return array
	 */
	private function get_blocks() {
		if ( ! empty( $this->valid_blocks ) ) {
			return $this->valid_blocks;
		}

		$blocks = apply_filters( 'sellkit_blocks_list', [
			'blocks/accept-reject-button',
			'blocks/checkout',
			'blocks/order-cart-details',
			'blocks/optin',
		] );

		if ( class_exists( 'Sellkit_Pro' ) ) {
			array_push( $blocks, 'blocks/smart-coupon' );
		}

		$this->valid_blocks = $blocks;

		return $blocks;
	}

	/**
	 * Register sellkit block.
	 *
	 * @since 2.3.0
	 */
	public function register_blocks() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wp-block-library' );

		$this->load_flatpicker();

		wp_enqueue_script(
			'sellkit-blocks-script',
			sellkit()->plugin_url() . 'assets/dist/blocks/sellkit-blocks.js',
			[ 'react', 'wp-blocks', 'lodash', 'wp-components', 'wp-editor', 'sellkit-flatpickr' ],
			sellkit()->version(),
			true
		);

		wp_localize_script(
			'sellkit-blocks-script',
			'sellkitBlocks',
			$this->get_localize_data()
		);

		foreach ( $this->blocks as $index => $block ) {
			if ( ! $block->is_active() ) {
				continue;
			}

			$block_data = explode( '/', $index );
			$block_name = $block_data[1];

			$deps = [ 'sellkit-blocks-script' ];

			if ( method_exists( $block, 'has_inner_blocks' ) ) {
				wp_enqueue_script(
					"sellkit-inner-blocks-{$block_name}-script",
					sellkit()->plugin_url() . "assets/dist/blocks/{$block_name}/inner-blocks.js",
					[ 'wp-blocks', 'wp-element', 'wp-editor' ],
					sellkit()->version(),
					true
				);

				$deps[] = "sellkit-inner-blocks-{$block_name}-script";
			}

			wp_enqueue_script(
				"sellkit-block-{$block_name}-script",
				sellkit()->plugin_url() . "assets/dist/blocks/{$block_name}.js",
				$deps,
				sellkit()->version(),
				true
			);
		}
	}

	/**
	 * Load blocks codes.
	 *
	 * @since 2.3.0
	 */
	public function load_block_editor() {
		global $pagenow;

		sellkit()->load_files( [
			'contact-segmentation/class',
			'contact-segmentation/conditions',
		] );

		if ( ! empty( $this->blocks ) || ! is_admin() || 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
			return;
		}

		$this->post_id = sellkit_htmlspecialchars( INPUT_GET, 'post' );

		if ( empty( $this->post_id ) ) {
			if ( ! class_exists( 'Sellkit_Pro' ) ) {
				return;
			}

			add_filter( 'sellkit_blocks_list', function() {
				return [ 'blocks/smart-coupon' ];
			} );
		}

		$blocks = $this->get_blocks();

		foreach ( $blocks as $block ) {
			$block_data = explode( '/', $block );
			$block_name = $block_data[1];

			if ( isset( $this->blocks[ $block ] ) ) {
				continue;
			}

			$class_name = str_replace( '-', ' ', $block_name );
			$class_name = str_replace( ' ', '_', ucwords( $class_name ) );
			$class_name = "Sellkit\blocks\Render\\{$class_name}";
			$class_path = 'block-editor/' . $block . '/index';

			sellkit()->load_files( [
				$class_path,
			] );

			$new_class = new $class_name( $this->post_id );

			$this->register_inner_blocks_by_parent( $new_class );

			$this->blocks[ $block ] = $new_class;
			$new_class->register_block_meta();
		}
	}

	/**
	 * Load blocks on frontend.
	 *
	 * @since 2.3.0
	 */
	public function load_block_frontend() {
		global $post;

		if ( empty( $post->post_content ) ) {
			return;
		}

		$this->post_id = $post->ID;

		$blocks = $this->get_blocks();

		if ( empty( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block ) {
			$block_data = explode( '/', $block );
			$block_name = $block_data[1];

			if ( ! has_block( 'sellkit-blocks/' . $block_name ) ) {
				continue;
			}

			if ( isset( $this->blocks[ $block ] ) ) {
				continue;
			}

			$class_name = str_replace( '-', ' ', $block_name );
			$class_name = str_replace( ' ', '_', ucwords( $class_name ) );
			$class_name = "Sellkit\blocks\Render\\{$class_name}";
			$class_path = 'block-editor/' . $block . '/index';

			sellkit()->load_files( [
				$class_path,
			] );

			$new_class = new $class_name( $this->post_id );

			$this->register_inner_blocks_by_parent( $new_class );

			$this->blocks[ $block ] = $new_class;

			if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( "sellkit-blocks/{$block_name}" ) ) {
				$new_class->register_block_meta();
			}
		}
	}

	/**
	 * Load flatpicker scripts.
	 *
	 * @since 2.3.0
	 */
	public function load_flatpicker() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'sellkit-flatpickr',
			sellkit()->plugin_url() . 'assets/lib/flatpickr/flatpickr' . $suffix . '.js',
			[ 'jquery' ],
			'4.1.4',
			true
		);

		if ( 'default' !== $this->date_localization() ) {
			wp_enqueue_script( 'sellkit-flatpickr-localize',
				sellkit()->plugin_url() . 'assets/lib/flatpickr-locale/' . $this->date_localization . '.js',
				[ 'sellkit-flatpickr' ],
				'4.1.4',
				true
			);
		}

		wp_enqueue_style(
			'sellkit-flatpickr-css',
			sellkit()->plugin_url() . 'assets/lib/flatpickr/flatpickr' . $suffix . '.css',
			[],
			sellkit()->version()
		);

		wp_enqueue_style(
			'sellkit-intl-tel-input',
			sellkit()->plugin_url() . 'assets/dist/css/intl-tel-input' . $suffix . '.css',
			[],
			sellkit()->version()
		);
	}

	/**
	 * Load google map script.
	 *
	 * @param string $api_key Google map api key.
	 * @since 2.3.0
	 */
	public function load_google_map( $api_key ) {
		wp_enqueue_script(
			'sellkit_google_places_api',
			"https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places",
			[],
			'1.0.0',
			false
		);
	}

	/**
	 * Load scripts in frontend.
	 *
	 * @param string $block_name    Current block name.
	 * @param string $script_file   Script file name.
	 * @param array  $deps          Script dependecies.
	 * @param array  $localize_data Localized data.
	 * @since 2.3.0
	 */
	public static function load_scripts( $block_name, $script_file = '', $deps = [], $localize_data = [] ) {
		if ( ! has_block( 'sellkit-blocks/' . $block_name ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'sellkit-intl-tel-input',
			sellkit()->plugin_url() . 'assets/dist/css/intl-tel-input' . $suffix . '.css',
			[],
			sellkit()->version()
		);

		wp_enqueue_script(
			'sellkit-blocks-script',
			sellkit()->plugin_url() . 'assets/dist/blocks/sellkit-blocks.js',
			[ 'react', 'wp-blocks', 'lodash', 'wp-components', 'wp-editor' ],
			sellkit()->version(),
			true
		);

		$file = empty( $script_file ) ? $block_name : $script_file;

		$dependencies = [ 'sellkit-blocks-script', 'funnel-frontend' ];

		if ( ! empty( $deps ) ) {
			$dependencies = $deps;
		}

		wp_enqueue_script(
			"sellkit-block-{$file}-script",
			sellkit()->plugin_url() . "assets/dist/blocks/{$file}.js",
			$dependencies,
			sellkit()->version(),
			true
		);

		if ( ! empty( $localize_data ) ) {
			wp_localize_script(
				"sellkit-block-{$file}-script",
				'sellkitBlocks',
				$localize_data
			);
		}
	}

	/**
	 * Register inner blocks by parent.
	 *
	 * @param Object $parent_class Parent class name.
	 * @since 2.3.0
	 * @return void
	 */
	private function register_inner_blocks_by_parent( $parent_class ) {
		if ( ! method_exists( $parent_class, 'has_inner_blocks' ) ) {
			return;
		}

		$inner_blocks = $parent_class->get_inner_block();

		sellkit()->load_files( $inner_blocks );

		foreach ( $inner_blocks as $key => $value ) {
			if ( isset( $this->inner_blocks[ "blocks/{$key}" ] ) ) {
				continue;
			}

			$inner_block_class = 'Sellkit\Blocks\Inner_Block\\' . str_replace( '-', '_', ucwords( $key ) );

			if ( ! class_exists( $inner_block_class ) ) {
				continue;
			}

			$inner_block_instance = new $inner_block_class( $this->post_id );

			$this->inner_blocks[ "blocks/{$key}" ] = $inner_block_instance;

			if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( "sellkit-inner-blocks/{$key}" ) ) {
				$inner_block_instance->register_block_meta();
			}
		}
	}

	/**
	 * Get date localize data.
	 *
	 * @since 2.3.0
	 * @return string
	 */
	private function date_localization() {
		if ( ! empty( $this->date_localization ) ) {
			return $this->date_localization;
		}

		$wp_locale = get_locale();

		$locales = [
			'af'             => '',   // 'Afrikaans'
			'ar'             => 'ar', // 'Arabic'
			'ary'            => 'ar', // 'Moroccan Arabic'
			'as'             => '',   // 'Assamese'
			'azb'            => 'az', // 'South Azerbaijani'
			'az'             => 'az', // 'Azerbaijani'
			'bel'            => 'be', // 'Belarusian'
			'bg_BG'          => 'bg', // 'Bulgarian'
			'bn_BD'          => 'bn', // 'Bengali (Bangladesh)'
			'bo'             => '',   // 'Tibetan'
			'bs_BA'          => 'bs', // 'Bosnian'
			'ca'             => 'cat', // 'Catalan'
			'ceb'            => '',   // 'Cebuano'
			'cs_CZ'          => 'cs', // 'Czech'
			'cy'             => 'cy', // 'Welsh'
			'da_DK'          => 'da', // 'Danish'
			'de_CH_informal' => 'de', // 'German (Switzerland, Informal)'
			'de_CH'          => 'de', // 'German (Switzerland)'
			'de_DE'          => 'de', // 'German'
			'de_DE_formal'   => 'de', // 'German (Formal)'
			'de_AT'          => 'de', // 'German (Austria)'
			'dzo'            => '',   // 'Dzongkha'
			'el'             => 'gr', // 'Greek'
			'en_GB'          => 'en', // 'English (UK)'
			'en_AU'          => 'en', // 'English (Australia)'
			'en_CA'          => 'en', // 'English (Canada)'
			'en_ZA'          => 'en', // 'English (South Africa)'
			'en_NZ'          => 'en', // 'English (New Zealand)'
			'eo'             => 'eo', // 'Esperanto'
			'es_CL'          => 'es', // 'Spanish (Chile)'
			'es_ES'          => 'es', // 'Spanish (Spain)'
			'es_MX'          => 'es', // 'Spanish (Mexico)'
			'es_GT'          => 'es', // 'Spanish (Guatemala)'
			'es_CR'          => 'es', // 'Spanish (Costa Rica)'
			'es_CO'          => 'es', // 'Spanish (Colombia)'
			'es_PE'          => 'es', // 'Spanish (Peru)'
			'es_VE'          => 'es', // 'Spanish (Venezuela)'
			'es_AR'          => 'es', // 'Spanish (Argentina)'
			'et'             => 'et', // 'Estonian'
			'eu'             => 'es', // 'Basque'
			'fa_IR'          => 'fa', // 'Persian'
			'fi'             => 'fi', // 'Finnish'
			'fr_CA'          => 'fr', // 'French (Canada)'
			'fr_FR'          => 'fr', // 'French (France)'
			'fr_BE'          => 'fr', // 'French (Belgium)'
			'fur'            => '',   // 'Friulian'
			'gd'             => 'ga', // 'Scottish Gaelic'
			'gl_ES'          => 'es', // 'Galician'
			'gu'             => '',   // 'Gujarati'
			'haz'            => '',   // 'Hazaragi'
			'he_IL'          => 'he', // 'Hebrew'
			'hi_IN'          => 'hi', // 'Hindi'
			'hr'             => 'hr', // 'Croatian'
			'hsb'            => '',   // 'Upper Sorbian'
			'hu_HU'          => 'hu', // 'Hungarian'
			'hy'             => '',   // 'Armenian'
			'id_ID'          => 'id', // 'Indonesian'
			'is_IS'          => 'is', // 'Icelandic'
			'it_IT'          => 'it', // 'Italian'
			'ja'             => 'ja', // 'Japanese'
			'jv_ID'          => '',   // 'Javanese'
			'ka_GE'          => 'ka', // 'Georgian'
			'kab'            => '',   // 'Kabyle'
			'kk'             => 'kz', // 'Kazakh'
			'km'             => 'km', // 'Khmer'
			'kn'             => '',   // 'Kannada'
			'ko_KR'          => 'ko', // 'Korean'
			'ckb'            => '',   // 'Kurdish (Sorani)'
			'lo'             => '',   // 'Lao'
			'lt_LT'          => 'lt', // 'Lithuanian'
			'lv'             => 'lv', // 'Latvian'
			'mk_MK'          => 'mk', // 'Macedonian'
			'ml_IN'          => '',   // 'Malayalam'
			'mn'             => 'mn', // 'Mongolian'
			'mr'             => '',   // 'Marathi'
			'ms_MY'          => 'ms', // 'Malay'
			'my_MM'          => 'my', // 'Myanmar (Burmese)'
			'nb_NO'          => 'no', // 'Norwegian (Bokmål)'
			'ne_NP'          => '',   // 'Nepali'
			'nl_NL'          => 'nl', // 'Dutch'
			'nl_NL_formal'   => 'nl', // 'Dutch (Formal)'
			'nl_BE'          => 'nl', // 'Dutch (Belgium)'
			'nn_NO'          => 'no', // 'Norwegian (Nynorsk)'
			'oci'            => '',   // 'Occitan'
			'pa_IN'          => 'pa', // 'Punjabi'
			'pl_PL'          => 'pl', // 'Polish'
			'ps'             => '',   // 'Pashto'
			'pt_BR'          => 'pt', // 'Portuguese (Brazil)'
			'pt_AO'          => 'pt', // 'Portuguese (Angola)'
			'pt_PT'          => 'pt', // 'Portuguese (Portugal)'
			'pt_PT_ao90'     => 'pt', // 'Portuguese (Portugal, AO90)'
			'rhg'            => '',   // 'Rohingya'
			'ro_RO'          => 'ro', // 'Romanian'
			'ru_RU'          => 'ru', // 'Russian'
			'sah'            => '',   // 'Sakha'
			'si_LK'          => 'si', // 'Sinhala'
			'sk_SK'          => 'sk', // 'Slovak'
			'skr'            => '',   // 'Saraiki'
			'sl_SI'          => 'sl', // 'Slovenian'
			'sq'             => 'sq', // 'Albanian'
			'sr_RS'          => 'sr', // 'Serbian'
			'sv_SE'          => 'sv', // 'Swedish'
			'sw'             => '',   // 'Swahili'
			'szl'            => '',   // 'Silesian'
			'ta_IN'          => '',   // 'Tamil'
			'te'             => '',   // 'Telugu'
			'th'             => 'th', // 'Thai'
			'tl'             => '',   // 'Tagalog'
			'tr_TR'          => 'tr', // 'Turkish'
			'tt_RU'          => '',   // 'Tatar'
			'tah'            => '',   // 'Tahitian'
			'ug_CN'          => '',   // 'Uighur'
			'uk'             => 'uk', // 'Ukrainian'
			'ur'             => '',   // 'Urdu'
			'uz_UZ'          => '',   // 'Uzbek'
			'vi'             => 'vn', // 'Vietnamese'
			'zh_HK'          => 'zh', // 'Chinese (Hong Kong)'
			'zh_TW'          => 'zh-tw', // 'Chinese (Taiwan)'
			'zh_CN'          => 'zh', // 'Chinese (China)'
		];

		$result = array_key_exists( $wp_locale, $locales ) ? $locales[ $wp_locale ] : 'default';

		if ( 'en' === $result || '' === $result ) {
			$result = 'default';
		}

		$this->date_localization = $result;

		return $result;
	}

	/**
	 * Get localize data.
	 *
	 * @since 2.3.0
	 * @return array
	 */
	public function get_localize_data() {
		$upload_dir = wp_upload_dir();

		return [
			'wc' => [
				'placeholder' => wc_placeholder_img_src(),
				'shipping_destination' => get_option( 'woocommerce_ship_to_destination', 'billing' ),
			],
			'optin' => [
				'types' => [
					'text'       => esc_html__( 'Text', 'sellkit' ),
					'number'     => esc_html__( 'Number', 'sellkit' ),
					'email'      => esc_html__( 'Email', 'sellkit' ),
					'tel'        => esc_html__( 'Tel', 'sellkit' ),
					'textarea'   => esc_html__( 'Textarea', 'sellkit' ),
					'date'       => esc_html__( 'Date', 'sellkit' ),
					'time'       => esc_html__( 'Time', 'sellkit' ),
					'checkbox'   => esc_html__( 'Checkbox', 'sellkit' ),
					'radio'      => esc_html__( 'Radio', 'sellkit' ),
					'select'     => esc_html__( 'Select', 'sellkit' ),
					'address'    => esc_html__( 'Address', 'sellkit' ),
					'acceptance' => esc_html__( 'Acceptance', 'sellkit' ),
					'hidden'     => esc_html__( 'Hidden', 'sellkit' ),
				],
				'telTypes' => [
					'all' => esc_html__( 'All', 'sellkit' ),
					'0'   => esc_html__( 'Fixed Line', 'sellkit' ),
					'1'   => esc_html__( 'Mobile', 'sellkit' ),
					'2'   => esc_html__( 'Fixed Line or Mobile', 'sellkit' ),
					'3'   => esc_html__( 'Toll Free', 'sellkit' ),
					'4'   => esc_html__( 'Premium Rate', 'sellkit' ),
					'5'   => esc_html__( 'Shared Cost', 'sellkit' ),
					'6'   => esc_html__( 'VOIP', 'sellkit' ),
					'7'   => esc_html__( 'Personal Number', 'sellkit' ),
					'8'   => esc_html__( 'Pager', 'sellkit' ),
					'9'   => esc_html__( 'UAN', 'sellkit' ),
					'10'  => esc_html__( 'Voicemail', 'sellkit' ),
				],
				'dateLocalization' => $this->date_localization(),
				'crm' => [
					'activeCampaign' => [
						'key' => sellkit_get_option( 'activecampaign_api_key', '' ),
						'url' => sellkit_get_option( 'activecampaign_api_url', '' ),
					],
					'convertKit' => sellkit_get_option( 'convertkit_api_key', '' ),
					'drip' => sellkit_get_option( 'drip_api_key', '' ),
					'getResponse' => sellkit_get_option( 'getresponse_api_key', '' ),
					'mailchimp' => sellkit_get_option( 'mailchimp_api_key', '' ),
					'mailerlite' => sellkit_get_option( 'mailerlite_api_key', '' ),
				]
			],
			'settings' => [
				'url' => admin_url() . 'admin.php?page=sellkit-settings#/',
				'admin' => admin_url(),
			],
			'wpUploadData' => [
				'baseUrl' => $upload_dir['baseurl'],
				'baseDir' => $upload_dir['basedir'],
			],
		];
	}

}

Sellkit_Blocks::get_instance();
