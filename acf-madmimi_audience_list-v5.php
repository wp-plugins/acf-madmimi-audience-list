<?php

class acf_field_madmimi_audience_list extends acf_field {

	var $madmimi;
	var $madmimi_lists;
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function __construct() {
		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/

		$this->name = 'madmimi_audience_list';
		$this->madmimi = new MadMimi(mal_madmimi_username(), mal_madmimi_api_key() );
		$this->set_madmimi_lists();


		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/

		$this->label = __('MadMimi Audience List', 'acf-madmimi_audience_list');


		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/

		$this->category = 'choice';


		/*
		*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		*/

		$this->defaults = array();


		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('madmimi_audience_list', 'error');
		*/

		$this->l10n = array();


		// do not delete!
    	parent::__construct();

	}

	function set_madmimi_lists() {
		$list_array = get_transient( 'mal_audience_lists' );
		if( empty( $list_array ) ) {

			$lists = $this->madmimi->Lists();
			$lists = new SimpleXMLElement( $lists );
			$list_array = array();
			foreach( $lists as $list ) {
				$list = (array) $list;
				if( empty( $list['@attributes']['display_name'] ) ) {
					$list['@attributes']['display_name'] = $list['@attributes']['name'];
				}
				$list_array[$list['@attributes']['id']] = $list['@attributes'];
			}

			set_transient( 'mal_audience_lists', $list_array, HOUR_IN_SECONDS );
		}
		$this->madmimi_lists = $list_array;
	}


	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field_settings( $field ) {

		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name (font_size)
		*/

		acf_render_field_setting( $field, array(
			'label'			=> __('Return Format','acf-madmimi_audience_list'),
			'instructions'	=> __('Set the type of data you would like the field to return','acf-madmimi_audience_list'),
			'type'			=> 'select',
			'name'			=> 'return',
			'choices'       => array(
				'array' => __( 'Array of all API Data', 'acf-madmimi_audience_list' ),
				'id' => __( 'List ID', 'acf-madmimi_audience_list' ),
				'name' => __( 'Name', 'acf-madmimi_audience_list' ),
				'display_name' => __( 'Display Name', 'acf-madmimi_audience_list' ),
				'subscriber_count' => __( 'Subscrber Count', 'acf-madmimi_audience_list' )
			)
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Allow Multiple Selections','acf-madmimi_audience_list'),
			'message'    	=> __('Allow multiple?','acf-madmimi_audience_list'),
			'type'			=> 'true_false',
			'name'			=> 'multiple',
			'layout'		=> 'horizontal',
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Allow Null','acf-madmimi_audience_list'),
			'message'    	=> __('Allow no value to be selected?','acf-madmimi_audience_list'),
			'type'			=> 'true_false',
			'name'			=> 'allow_null',
			'layout'		=> 'horizontal',
		));


	}



	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field( $field ) {
		$multiple = ( !empty( $field['multiple'] ) ) ? 'multiple="multiple"' : '';
		$name = ( !empty( $field['multiple'] ) ) ? esc_attr($field['name']) . '[]' : esc_attr($field['name']);

		echo '<select '.$multiple.' name="'.$name.'">';

		if( !empty( $field['allow_null'] ) ) {
			echo '<option value="">' . __( 'No List Selected', 'acf-madmimi_audience_list' ) . '</option>';
		}

		foreach( $this->madmimi_lists as $list ) {
			$selected = ( (is_array( $field['value'] ) && in_array( $list['id'], $field['value'] ) ) || ( !is_array( $field['value'] ) && $field['value'] == $list['id'] ) ) ? 'selected="selected"' : '';
			echo '<option '.$selected.' value="'.$list['id'].'">' . $list['display_name'] . '</option>';
		}
		echo '</select>';
	}



	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/


	function format_value( $value, $post_id, $field ) {

		// bail early if no value
		if( empty($value) ) {

			return $value;

		}

		$value_array = $value;
		if( !is_array( $value_array ) ) {
			$value_array = array( $value_array );
		}

		foreach( $value_array as $i => $value ) {

			if( empty( $field['return'] ) || $field['return'] == 'array' ) {
				$value_array[$i] = $this->madmimi_lists[$value];
			}
			else {
				$value_array[$i] = $this->madmimi_lists[$value][$field['return']];
			}

		}

		if( empty( $field['multiple'] ) ) {
			$value = $value_array[0];
		}
		else {
			$value = $value_array;
		}

		// return
		return $value;
	}




}


// create field
new acf_field_madmimi_audience_list();

?>
