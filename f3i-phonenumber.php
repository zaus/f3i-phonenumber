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
		
		
		add_filter(self::B.'_service_filter_post', array(&$this, 'post_filter'), 10, 5);
	}
	
	private static $util;
	
	function initUtil() {
		include('vendor/autoload.php');
		self::$util = \libphonenumber\PhoneNumberUtil::getInstance();
	}
	
	public function post_filter($post, $service, $form, $sid, $submission) {
		if(!isset($service[static::PARAM_FIELDS]) || empty($service[static::PARAM_FIELDS])) return $post;
		
		$phoneFields = $service[static::PARAM_FIELDS];
		$phoneFields = array_map('trim', explode(',', $phoneFields));
		
		_log(__CLASS__, $phoneFields, $post, $submission);
		
		// should we only look in the post, or also the submission?
		$target = $submission; // $post
		
		foreach($phoneFields as $field) {
			
			if(!isset($target[$field]) || empty($target[$field])) continue;
			
			$phonenumber = $target[$field];
			
			try {
				$proto = self::$util->parse($phonenumber, "CH");
			}
			catch(\libphonenumber\NumberParseException $e) {
				$proto = $e->getMessage();
				$post['f3iph-error-' . $field] = $e->getMessage();
			}
			
			_log(__CLASS__, $phonenumber, $proto);
		}
		
		return $post;
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
						<em class="description"><?php _e('List of field names to treat as phone numbers for parsing, comma-separated.', $P); ?></em>
					</div>
				</div>
			</fieldset>
		<?php
	}

}


// engage
new F3iPhonenumber;