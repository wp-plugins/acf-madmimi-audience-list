<?php

class acf_field_madmimi_audience_list extends acf_field {

	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options

	var $madmimi;
	var $madmimi_lists;

	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/

	function __construct()
	{
		// vars

		$this->madmimi = new MadMimi(mal_madmimi_username(), mal_madmimi_api_key() );
		$this->set_madmimi_lists();
		$this->name = 'madmimi_audience_list';
		$this->label = __('MadMimi Audience List');
		$this->category = __("Choice",'acf'); // Basic, Content, Choice, etc
		$this->defaults = array(
			// add default here to merge into your field.
			// This makes life easy when creating the field options as you don't need to use any if( isset('') ) logic. eg:
			//'preview_size' => 'thumbnail'
		);


		// do not delete!
    	parent::__construct();


    	// settings
		$this->settings = array(
			'path' => apply_filters('acf/helpers/get_path', __FILE__),
			'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
			'version' => '1.0.0'
		);

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
	*  create_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like below) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/

	function create_options( $field )
	{
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/

		// key is needed in the field names to correctly save the data
		$key = $field['name'];


		// Create Field Options HTML
		?>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Return Format",'acf-madmimi_audience_list'); ?></label>
		<p class="description"><?php _e("Set the type of data you would like the field to return",'acf-madmimi_audience_list'); ?></p>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'		=>	'select',
			'name'		=>	'fields['.$key.'][return]',
			'value'		=>	$field['return'],
			'choices'       => array(
				'array' => __( 'Array of all API Data', 'acf-madmimi_audience_list' ),
				'id' => __( 'List ID', 'acf-madmimi_audience_list' ),
				'name' => __( 'Name', 'acf-madmimi_audience_list' ),
				'display_name' => __( 'Display Name', 'acf-madmimi_audience_list' ),
				'subscriber_count' => __( 'Subscrber Count', 'acf-madmimi_audience_list' )
			)
		));

		?>
	</td>
</tr>


<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Allow Multiple",'acf-madmimi_audience_list'); ?></label>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'		=>	'true_false',
			'name'		=>	'fields['.$key.'][multiple]',
			'value'		=>	$field['multiple'],
			'layout'	=>	'horizontal',
			'message'     => __('Allow multiple?', 'acf-madmimi_audience_list' )
		));

		?>
	</td>
</tr>


<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Allow Null",'acf-madmimi_audience_list'); ?></label>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'		=>	'true_false',
			'name'		=>	'fields['.$key.'][allow_null]',
			'value'		=>	$field['allow_null'],
			'layout'	=>	'horizontal',
			'message'     => __('Allow no value to be selected', 'acf-madmimi_audience_list' )
		));

		?>
	</td>
</tr>


		<?php

	}


	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function create_field( $field )
	{
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
	*  This filter is applied to the $value after it is loaded from the db and before it is passed to the create_field action
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/

	function format_value( $value, $post_id, $field )
	{

				// bail early if no value
				if( empty($value) ) {

					return $value;

				}

				$value_array = $value;
				if( !is_array( $value_array ) ) {
					$value_array = array( $value_array );
				}

				foreach( $value_array as $i => $value ) {

					if( $field['return'] == 'array' ) {
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
