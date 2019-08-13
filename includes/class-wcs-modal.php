<?php
/**
 * A class to create and display a modal popup.
 *
 * @package  WooCommerce Subscriptions
 * @category Class
 * @since    2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WCS_Modal {

	/**
	 * The content to display inside the modal body.
	 *
	 * Can be plain text, raw HTML or a template file path.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * The type of content to display.
	 *
	 * Can be 'plain-text', 'html', or 'template'.
	 *
	 * @var string
	 */
	private $content_type;

	/**
	 * A selector of the element which triggers the modal to be displayed.
	 *
	 * @var string
	 */
	private $trigger = '';

	/**
	 * The modal heading.
	 *
	 * @var string
	 */
	private $heading = '';

	/**
	 * The modal actions.
	 *
	 * @var array
	 */
	private $actions = array();

	/**
	 * Registers the scripts and stylesheets needed to display the modals.
	 *
	 * The required files will only be enqueued once. Subsequent calls will do nothing.
	 *
	 * @since 2.6.0
	 */
	public static function register_scripts_and_styles() {
		static $registered = false;

		// No need to proceed if the styles and scripts have already been enqueued.
		if ( $registered ) {
			return;
		}

		$registered = true;

		// If the scripts are being registered late (after 'wp_enqueue_scripts' has run), it's safe to enqueue them immediately.
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			self::enqueue_scripts_and_styles();
		} else {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_and_styles' ) );
		}
	}

	/**
	 * Enqueues the modal scripts and styles.
	 *
	 * @since 2.6.0
	 */
	public static function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'wcs-modal-scripts', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/modal.js', array( 'jquery' ), WC_Subscriptions::$version, true );
		wp_enqueue_style( 'wcs-modal-styles', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/css/modal.css', array(), WC_Subscriptions::$version );
	}

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 *
	 * @param string $content The content to display in the modal.
	 * @param string $type    Optional. The modal content type. Can be 'plain-text', 'html', or 'template'. Default is 'plain-text'.
	 */
	function __construct( $content_type, $content, $trigger, $heading = '', $actions = array() ) {
		$this->content_type = $content_type;
		$this->trigger      = $trigger;
		$this->heading      = $heading;
		$this->actions      = $actions;

		// Allow callers to provide the callback without any parameters. Assuming the content provided is the callback.
		if ( 'callback' === $this->content_type && ! isset( $content['parameters'] ) ) {
			$this->content = array(
				'callback'   => $content,
				'parameters' => array(),
			);
		} else {
			$this->content = $content;
		}

		self::register_scripts_and_styles();
	}

	/**
	 * Prints the modal HTML.
	 *
	 * @since 2.6.0
	 */
	public function print_html() {
		wc_get_template( 'html-modal.php', array( 'modal' => $this ), '', plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/' );
	}

	/**
	 * Prints the modal inner content.
	 *
	 * @since 2.6.0
	 */
	public function print_content() {
		switch ( $this->content_type ) {
			case 'plain-text':
				echo '<p>' . wp_kses_post( $this->content ) . '</p>';
				break;
			case 'html':
				echo wp_kses_post( $this->content );
				break;
			case 'template':
				wc_get_template( $this->content['template_name'], $this->content['args'], '', $this->content['template_path'] );
				break;
			case 'callback':
				call_user_func_array( $this->content['callback'], $this->content['parameters'] );
				break;
		}
	}

	/**
	 * Determines if the modal has a heading.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function has_heading() {
		return ! empty( $this->heading );
	}

	/**
	 * Determines if the modal has actions.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function has_actions() {
		return ! empty( $this->actions );
	}

	/**
	 * Adds a button or link action which will be printed in the modal footer.
	 *
	 * @since 2.6.0
	 *
	 * @param array $action {
	 *     @type string Optional. The element type. Can be 'button' or 'a'. Default 'a' (link element).
	 *     @type array  Optional. An array of HTML attributes in a array( 'attribute' => 'value' ) format. The value can also be an array of attribute values. Default is empty array.
	 *     @type string Optional. The text should appear inside the button or a tag. Default is empty string.
	 * }
	 */
	public function add_action( $action_args ) {
		$action = wp_parse_args( $action_args, array(
			'type'       => 'a',
			'text'       => '',
			'attributes' => array(
				'class' => 'button',
			),
		) );

		$this->actions[] = $action;
	}

	/**
	 * Returns the modal heading.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public function get_heading() {
		return $this->heading;
	}

	/**
	 * Returns the array of actions.
	 *
	 * @since 2.6.0
	 *
	 * @return array The modal actions.
	 */
	public function get_actions() {
		return $this->actions;
	}

	/**
	 * Returns the modal's trigger selector.
	 *
	 * @since 2.6.0
	 *
	 * @return string The trigger element's selector.
	 */
	public function get_trigger() {
		return $this->trigger;
	}

	/**
	 * Returns a flattened string of HTML element attributes from an array of attributes and values.
	 *
	 * @since 2.6.0
	 *
	 * @param array $attributes An array of attributes in a array( 'attribute' => 'value' ) or array( 'attribute' => array( 'value', 'value ) ).
	 * @return string
	 */
	public function get_attribute_string( $attributes ) {
		foreach ( $attributes as $attribute => $values ) {
			$attributes[ $attribute ] = $attribute . '="' . implode( ' ', array_map( 'esc_attr', (array) $values ) ) . '"';
		}

		return implode( ' ', $attributes );
	}
}
