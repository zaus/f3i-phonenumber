<?php
/*

Plugin Name: Forms: 3rdparty-Integration Phone Numbers
Plugin URI: https://github.com/zaus/f3i-phonenumber
Description: Parses forms-3rdparty submission phone number fields
Author: zaus
Version: 0.4
Author URI: http://drzaus.com
Changelog:
	0.1	initial, composer dependency
*/

class F3iPhonenumber {

	const N = 'F3iPhonenumber';
	const B = 'Forms3rdPartyIntegration';
	
	const PARAM_FIELDS = 'f3iph';
	
	public function __construct() {
// 		require('F3iPhonenumber-options.php');
// 
// 		// only on frontend pages
// 		if(is_admin()) {
// 			F3iPhonenumberOptions::instance(__FILE__);
// 			return;
// 		}

		if(is_admin()) {
			// configure whether to attach or not, how
			add_filter(self::B.'_service_settings', array(&$this, 'service_settings'), 10, 3);
			return;
		}
		
		$this->initUtil();
		
		
		add_filter(self::B.'_get_submission', array(&$this, 'get_submission'), 15, 3);
	}
	
	private static $util;
	
	function initUtil() {
		include('vendor/autoload.php');
		self::$util = \libphonenumber\PhoneNumberUtil::getInstance();
	}
	
	public function get_submission($submission, $form, $service) {
		if(!isset($service[static::PARAM_FIELDS]) || empty($service[static::PARAM_FIELDS])) return $submission;
		
		$phoneFields = $service[static::PARAM_FIELDS];
		parse_str($phoneFields, $phoneFields);
		
		//$phoneFields = array_map('trim', explode(',', $phoneFields));
		
		### _log(__CLASS__, $phoneFields, $submission);
		
		foreach($phoneFields as $field=>$format) {
			
			if(!isset($submission[$field]) || empty($submission[$field])) continue;
			
			$phonenumber = $submission[$field];
			
			try {
				$proto = self::$util->parse($phonenumber, !isset($format) || empty($format) ? "US" : $format);
				
				### _log('parsed proto', $field, $format, $proto);
				
				// attach parts to submission
				$args = (array) $proto; // get_object_vars($proto)
				_log($proto, $args);
				
				foreach($args as $k=>$v) $submission[$field . '-' . $k] = $v;
			}
			catch(\libphonenumber\NumberParseException $e) {
				$proto = $e->getMessage();
				
				$submission['f3iph-error-' . $field] = sprintf("(%s/%s) %s", $field, $format, $e->getMessage());
			}
			
			### _log(__CLASS__, $phonenumber, $proto);
		}
		
		### _log(__CLASS__, $submission);
		return $submission;
	}
	
	
	public function service_settings($eid, $P, $entity) {
		?>

			<fieldset class="postbox"><legend class="hndle"><span><?php _e('Phone Number', $P); ?></span></legend>
				<div class="inside">
					<em class="description"><?php _e('Configure phone-number parsing.', $P) ?></em>

					<?php $field = static::PARAM_FIELDS; ?>
					<div class="field">
						<label for="<?php echo $field, '-', $eid ?>"><?php _e('Phone number configuration', $P); ?></label>
						<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : 'field_name=(%s) %s'?>" />
						<em class="description"><?php echo sprintf( __('List of field names to treat as phone numbers for parsing, given as URL-encoded querystring %s.', $P), '<code>field_name&field_name2=country-format</code>' ); ?></em>
					</div>
				</div>
			</fieldset>
		<?php
	}

}


// engage
new F3iPhonenumber;