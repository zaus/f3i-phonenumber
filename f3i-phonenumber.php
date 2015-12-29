<?php
/*

Plugin Name: Forms: 3rdparty-Integration Phone Numbers
Plugin URI: https://github.com/zaus/f3i-phonenumber
Description: Parses forms-3rdparty submission phone number fields
Author: zaus
Version: 0.2
Author URI: http://drzaus.com
Changelog:
	0.1	initial, composer dependency
	0.2 input/output formats
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
	
	/**
	 * The parts that will be attached to the submission as `FIELD-PART`
	 */
	private static $parts = array(
		'CountryCode',
		'NationalNumber',
		'Extension',
		'NumberOfLeadingZeros'
	);
	
	public function get_submission($submission, $form, $service) {
		if(!isset($service[static::PARAM_FIELDS]) || empty($service[static::PARAM_FIELDS])) return $submission;
		
		$phoneFields = $service[static::PARAM_FIELDS];
		parse_str($phoneFields, $phoneFields);
		
		//$phoneFields = array_map('trim', explode(',', $phoneFields));
		
		### _log(__CLASS__, $phoneFields, $submission);
		
		foreach($phoneFields as $field=>$format) {
			
			if(!isset($submission[$field]) || empty($submission[$field])) continue;
			
			$phonenumber = $submission[$field];
			if(!isset($format) || empty($format)) {
				$format = 'US';
			}
			else {
				list($format, $outformat) = explode(',', $format);
			}
			
			if(!isset($outformat)) $outformat = \libphonenumber\PhoneNumberFormat::INTERNATIONAL;
			
			try {
				$proto = self::$util->parse($phonenumber, !isset($format) || empty($format) ? "US" : $format);
				
				### _log('parsed proto', $field, $format, $proto);
				
				// attach parts to submission -- look at PhoneNumber.php
				// self::$util->format($proto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL)
				// $parts = unserialize($proto->serialize());
				
				// attach each expected part, even if empty
				$parts = array();
				foreach(self::$parts as $k) {
					$parts[$field . '-' . $k] = $proto->{'get' . $k}();
				}
				$parts[$field . '-Out'] = self::$util->format($proto, $outformat);
				
				### _log($phonenumber, $format, $outformat, $parts);
				
				$submission += $parts;
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
					<p class="description"><?php _e('The indicated fields will parsed and be attached to the submission array with the following keys:', $P) ?></p>
					<ul>
					<?php foreach(self::$parts as $part) { ?>
						<li><code>FIELD-</code><code><?php echo $part ?></code></li>
					<?php } ?>
						<li><code>FIELD-</code><code>Out</code> &mdash; <em>this will be reformatted according to an output format given (or default)</li>
					</ul>

					<?php $field = static::PARAM_FIELDS; ?>
					<div class="field">
						<label for="<?php echo $field, '-', $eid ?>"><?php _e('Phone number Fields:', $P); ?></label>
						<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : 'field_name=(%s) %s'?>" />
						<em class="description"><?php echo sprintf( __('List of field names to treat as phone numbers for parsing, given as URL-encoded querystring %s.  Please see <a href="%s">this library</a> for more information on country codes and formatting options, such as %s.', $P)
							, '<code>field_name&field_name2=country-format&field_name3=input-format,output-format</code>'
							, 'https://github.com/giggsey/libphonenumber-for-php'
							, '<code>\'US\'</code>, <code>\'GB\'</code>, <code>1</code>' ); ?></em>
					</div>
				</div>
			</fieldset>
		<?php
	}

}


// engage
new F3iPhonenumber;