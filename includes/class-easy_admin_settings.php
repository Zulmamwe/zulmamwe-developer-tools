<?php
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Easy_Admin_Settings' ) ){
	class Easy_Admin_Settings{
		
		public function __construct( $args = array() ){
			$defaults = array(
				'text_domain'	=> null,
				'option_name'	=> null,
				'menu_slug'		=> null,
				'page_title'	=> null,
				'menu_title'	=> null,
				'sections'		=> null
			);
			
			$args = wp_parse_args( $args, $defaults );
			foreach($args as $arg){
				if( is_null($arg) ){
					return;
				}
			}
			extract($args);
			
			$this->text_domain = $text_domain;
			$this->option_name = $option_name;
			$this->menu_slug = $menu_slug;
			$this->page_title = $page_title;
			$this->menu_title = $menu_title;
			$this->sections = $sections;
			$this->settings_error_slug = $this->menu_slug . '_messages';
			
			$this->create_callbacks();
			
			add_action( 'admin_init', array( $this, 'settings_init' ) );
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		}
		
		public function __call($name, $arguments){
			return call_user_func_array($this->{$name}, $arguments);
		}
		
		public function create_callbacks(){
			foreach($this->sections as $section_name => $section){
				
				$section_callback_name = "{$section_name}_cb";
				$description = isset($section['description']) ? $section['description'] : '';
				$this->$section_callback_name = function( $args ) use( $description ){
					if( !empty($description) ){
						?>
						<p id="<?= esc_attr( $args['id'] ) ?>" class="description"><?= $description ?></p>
						<?php
					}
				};
				
				foreach($section['fields'] as $field_name => $field){
					$field_callback_name = "{$field_name}_cb";
					$this->$field_callback_name = function( $args ) use( $field_name ){
						$options = get_option( $this->option_name );
						$value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';
						$this->render_form_field( $field_name, $args, $value );
					};
				}
				
			}
		}
		
		public function settings_init() {
			register_setting( $this->menu_slug, $this->option_name );
			
			foreach($this->sections as $section_name => $section){
				
				$section_callback_name = "{$section_name}_cb";
				add_settings_section(
					$section_name,
					$section['title'],
					array( $this, $section_callback_name ),
					$this->menu_slug
				);
				
				foreach($section['fields'] as $field_name => $field){
					$field_callback_name = "{$field_name}_cb";
					add_settings_field(
						$field_name,
						$field['label'],
						array( $this, $field_callback_name ),
						$this->menu_slug,
						$section_name,
						$field
					);
				}
				
			}
		}
		
		public function add_options_page(){
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				'manage_options',
				$this->menu_slug,
				array( $this, 'options_page_html' )
			);
		}
		
		public function options_page_html(){
			if( ! current_user_can( 'manage_options' ) ){
				return;
			}
			if ( isset( $_GET['settings-updated'] ) ) {
				add_settings_error( $this->settings_error_slug, 'settings_saved', __( 'Settings Saved', $this->text_domain ), 'updated' );
			}
			settings_errors( $this->settings_error_slug );
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
					<?php
					settings_fields( $this->menu_slug );
					do_settings_sections( $this->menu_slug );
					submit_button( __( 'Save Settings', $this->text_domain ) );
					?>
				</form>
			</div>
			<?php
		}
		
		public function render_form_field( $key, $args, $value = null ) {
			$defaults = array(
				'type'              => 'text',
				'label'             => '',
				'description'       => '',
				'placeholder'       => '',
				'maxlength'         => false,
				'required'          => false,
				'autocomplete'      => false,
				'id'                => $key,
				'class'             => array(),
				'label_class'       => array(),
				'input_class'       => array(),
				'return'            => false,
				'options'           => array(),
				'custom_attributes' => array(),
				'validate'          => array(),
				'default'           => '',
				'autofocus'         => '',
				'priority'          => '',
			);
			
			$args = wp_parse_args( $args, $defaults );
			
			$required = $args['required'] ? 'required' : '';

			if ( is_string( $args['label_class'] ) ) {
				$args['label_class'] = array( $args['label_class'] );
			}

			if ( is_null( $value ) ) {
				$value = $args['default'];
			}

			// Custom attribute handling.
			$custom_attributes         = array();
			$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

			if ( $args['maxlength'] ) {
				$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
			}

			if ( ! empty( $args['autocomplete'] ) ) {
				$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
			}

			if ( true === $args['autofocus'] ) {
				$args['custom_attributes']['autofocus'] = 'autofocus';
			}

			if ( $args['description'] ) {
				$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
			}

			if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
				foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			$field           = '';
			$label_id        = $args['id'];
			$name            = "{$this->option_name}[{$key}]";
			$sort            = $args['priority'] ? $args['priority'] : '';
			$field_container = '<div class="form-field %1$s" id="%2$s">%3$s</div>';

			switch ( $args['type'] ) {
				case 'textarea':
					$field .= '<textarea ' .
						'name="' . esc_attr( $name ) .'" ' .
						'class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' .
						'id="' . esc_attr( $args['id'] ) . '" ' .
						'placeholder="' . esc_attr( $args['placeholder'] ) . '" ' .
						( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) .
						( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) .
						implode( ' ', $custom_attributes ) .
						$required .
					'>' . esc_textarea( $value ) . '</textarea>';
					break;
				
				case 'checkbox':
					$field = '<input ' .
						'type="' . esc_attr( $args['type'] ) . '" ' .
						'class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' .
						'name="' . esc_attr( $name ) . '" ' .
						'id="' . esc_attr( $args['id'] ) . '" ' .
						'value="1" ' .
						checked( $value, 1, false ) .
						implode( ' ', $custom_attributes ) .
						$required .
					' />';
					break;
				
				case 'text':
				case 'password':
				case 'datetime':
				case 'datetime-local':
				case 'date':
				case 'month':
				case 'time':
				case 'week':
				case 'number':
				case 'email':
				case 'url':
				case 'tel':
					$field .= '<input ' .
						'type="' . esc_attr( $args['type'] ) . '" ' .
						'class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' .
						'name="' . esc_attr( $name ) . '" ' .
						'id="' . esc_attr( $args['id'] ) . '" ' .
						'placeholder="' . esc_attr( $args['placeholder'] ) . '" ' .
						'value="' . esc_attr( $value ) . '" ' .
						implode( ' ', $custom_attributes ) .
						$required .
					' />';
					break;
				
				case 'select':
					$field   = '';
					$options = '';
					if ( ! empty( $args['options'] ) ) {
						foreach ( $args['options'] as $option_key => $option_text ) {
							if ( '' === $option_key ) {
								// If we have a blank option, select2 needs a placeholder.
								if ( empty( $args['placeholder'] ) ) {
									$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', $this->text_domain );
								}
								$custom_attributes[] = 'data-allow_clear="true"';
							}
							$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_attr( $option_text ) . '</option>';
						}
						$field .= '<select ' .
							'name="' . esc_attr( $name ) . '" ' .
							'id="' . esc_attr( $args['id'] ) . '" ' .
							'class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' .
							implode( ' ', $custom_attributes ) . ' ' .
							'data-placeholder="' . esc_attr( $args['placeholder'] ) . '"' .
							$required .
							'>
								' . $options . '
							</select>';
					}
					break;
				
				case 'radio':
					$label_id = current( array_keys( $args['options'] ) );
					if ( ! empty( $args['options'] ) ) {
						foreach ( $args['options'] as $option_key => $option_text ) {
							$field .= '<p>';
							$field .= '<input type="radio" ' .
								'class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' .
								'value="' . esc_attr( $option_key ) . '" ' .
								'name="' . esc_attr( $name ) . '" ' .
								implode( ' ', $custom_attributes ) . ' ' .
								'id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" ' .
								checked( $value, $option_key, false ) .
								$required .
							' />';
							$field .= '<label ' .
								'for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" ' .
								'class="radio ' . implode( ' ', $args['label_class'] ) . '"' .
							'>' . $option_text . '</label>';
							$field .= '</p>';
						}
					}
					break;
				
			}

			if ( ! empty( $field ) ) {
				$field_html = '';

				/*
				if ( $args['label'] && 'checkbox' !== $args['type'] ) {
					$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
				}
				*/

				$field_html .= '<div class="input-wrapper">' . $field . '</div>';

				if ( $args['description'] ) {
					$field_html .= '<p class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</p>';
				}

				$container_class = esc_attr( implode( ' ', $args['class'] ) );
				$container_id    = esc_attr( $args['id'] ) . '_field';
				$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
			}

			if ( $args['return'] ) {
				return $field;
			} else {
				echo $field; // WPCS: XSS ok.
			}
		}
	}
}
