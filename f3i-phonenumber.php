<?php
/*

Plugin Name: Forms: 3rdparty-Integration Phone Numbers
Plugin URI: https://github.com/zaus/f3i-phonenumber
Description: Parses forms-3rdparty submission phone number fields
Author: zaus
Version: 0.4.2
Author URI: http://drzaus.com
Changelog:
	0.1	initial, composer dependency
	0.2	input/output formats
	0.3	use other submission fields as formats
	0.4	libphonenumber v7.4.5, get area code
	0.4.2	parse_str spaces quirk
*/

class F3iPhonenumber {

	const N = 'F3iPhonenumber';
	const B = 'Forms3rdPartyIntegration';
	
	const PARAM_FIELDS = 'f3iph';
	const FIELD_KEY_PREFIX = '##';
	
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
		require(__DIR__.'/vendor/autoload.php');
		self::$util = \libphonenumber\PhoneNumberUtil::getInstance();
	}
	
	/**
	 * The parts that will be attached to the submission as `FIELD-PART`; used to show list on admin page
	 */
	private static $parts = array(
		'CountryCode',
		'NationalNumber', //<-- we want instead to use the `NationalSignificantNumber`
		// the following 2 are "manually" determined
		'AreaCode',
		'Subscriber',
		'Extension',
		'NumberOfLeadingZeros'
		// == manually added ==
		// , 'Out'
	);
	
	private function format_check($format, $submission) {
		if(strpos($format, self::FIELD_KEY_PREFIX) !== false) {
			return $submission[substr($format, count(self::FIELD_KEY_PREFIX)+1)];
		}
		
		return $format;
	}
	
	public function get_submission($submission, $form, $service) {
		if(!isset($service[static::PARAM_FIELDS]) || empty($service[static::PARAM_FIELDS])) return $submission;
		
		// php >5.3.14 ??
		$phoneFields = $service[static::PARAM_FIELDS];
		parse_str($phoneFields, $phoneFields);

		// quirk -- spaces in keys get turned into underscores: http://php.net/manual/en/function.parse-str.php#76978
		foreach($phoneFields as $field=>$format) {
			if( strpos($service[static::PARAM_FIELDS], $field) === false && strpos($field, '_') !== false ) {
				unset($phoneFields[$field]);
				$field = str_replace('_', ' ', $field);
				$phoneFields[$field] = $format;
			}
		}
		
		//$phoneFields = array_map('trim', explode(',', $phoneFields));
		### _log(__CLASS__, $phoneFields, $submission);
		
		foreach($phoneFields as $field=>$format) {
			
			if(!isset($submission[$field]) || empty($submission[$field])) continue;
			
			$phonenumber = $submission[$field];
			if(!isset($format) || empty($format)) {
				$format = 'US';
			}
			else {
				// can't use list($format, $outformat) if only one thing listed...
				$format = explode(',', $format);
				// only set the output format if explicitly provided
				if(count($format) > 1) $outformat = end($format);
				$format = $format[0];
			}
			
			if(!isset($outformat)) $outformat = \libphonenumber\PhoneNumberFormat::INTERNATIONAL;
			
			// special formatting -- maybe use another submission field
			$format = $this->format_check($format, $submission);
			$outformat = $this->format_check($outformat, $submission);

			## _log('before parse', $field, $phonenumber, $format, $outformat);

			// also see http://giggsey.com/libphonenumber/?phonenumber=9197654321&country=US&language=en&region=US
			try {
				$proto = self::$util->parse($phonenumber, !isset($format) || empty($format) ? "US" : $format);
				
				### _log('parsed proto', $field, $format, $outformat, $proto);
				
				// attach parts to submission -- look at PhoneNumber.php
				// self::$util->format($proto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL)
				// $parts = unserialize($proto->serialize());

				// area code per https://github.com/googlei18n/libphonenumber/issues/46 and https://github.com/giggsey/libphonenumber-for-php/blob/4ca0df036abdab8fa7bdf7c81eec07d5da30068e/src/libphonenumber/PhoneNumberUtil.php#L567
				$acLen = self::$util->getLengthOfGeographicalAreaCode($proto);
				$nationalNumber = self::$util->getNationalSignificantNumber($proto);

				// attach each expected part, even if empty
				// foreach(self::$parts as $k) $parts[$field . '-' . $k] = $proto->{'get' . $k}();
				$parts = array(
					$field . '-CountryCode' => $proto->getCountryCode(),
					$field . '-NationalNumber' => $nationalNumber,
					$field . '-AreaCode' => $acLen > 0 ? substr($nationalNumber, 0, $acLen) : '',
					$field . '-Subscriber' => $acLen > 0 ? substr($nationalNumber, $acLen) : $nationalNumber,
					$field . '-Extension' => $proto->getExtension(),
					$field . '-NumberOfLeadingZeros' => $proto->getNumberOfLeadingZeros(),
					$field . '-Out' => self::$util->format($proto, $outformat),
				);

				### _log($phonenumber, $format, $outformat, $parts);
				
				$submission += $parts;
			}
			catch(\libphonenumber\NumberParseException $e) {
				$proto = $e->getMessage();
				
				$submission['f3iph-error-' . $field] = sprintf("(%s/%s) %s", $field, $format, $e->getMessage());
			}
			
			### _log(__CLASS__, $phonenumber, $proto);
		}
		
		### _log(__CLASS__ . '--after', $phoneFields, $submission);
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
						<li><code>FIELD_NAME-</code><code><?php echo $part ?></code></li>
					<?php } ?>
						<li><code>FIELD_NAME-</code><code>Out</code> &mdash; <em>this will be reformatted according to an output format given (or default)</li>
					</ul>

					<?php $field = static::PARAM_FIELDS; ?>
					<div class="field">
						<label for="<?php echo $field, '-', $eid ?>"><?php _e('Phone number Fields:', $P); ?></label>
						<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : 'field_name=(%s) %s'?>" />
						<em class="description"><?php echo sprintf( __('List of field names to treat as phone numbers for parsing, given as URL-encoded querystring %s.  Please see <a href="%s">this library</a> for more information on country codes and formatting options, such as %s, or reference another field name via prefix %s.', $P)
							, '<code>field_name&field2=country-format&field3=input-format,output-format&field4=##field5</code>'
							, 'https://github.com/giggsey/libphonenumber-for-php'
							, '<code>\'US\'</code>, <code>\'GB\'</code>, <code>1</code>'
							, '<code>' . self::FIELD_KEY_PREFIX . '</code>'
							 ); ?></em>
					</div>
				</div>
			</fieldset>
		<?php
	}

}


// engage
new F3iPhonenumber;