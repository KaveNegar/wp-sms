<?php
class smshooshmand extends WP_SMS {
	private $wsdl_link = "http://smshooshmand.com/class/sms/webservice3/server.php?wsdl";
	private $client = null;
	public $tariff = "http://smshooshmand.com/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "09xxxxxxxx";
		
		if(!class_exists('nusoap_client'))
			include_once dirname( __FILE__ ) . '/../classes/nusoap.class.php';
		
		$this->client = new nusoap_client($this->wsdl_link,array('trace'=>true));
		
		$this->client->soap_defencoding = 'UTF-8';
		$this->client->decode_utf8 = true;
	}

	public function SendSMS() {
		// Check gateway credit
		if( is_wp_error($this->GetCredit()) ) {
			return new WP_Error( 'account-credit', __('Your account does not credit for sending sms.', 'wp-sms') );
		}
		
		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 * @param string $this->from sender number.
		 */
		$this->from = apply_filters('wp_sms_from', $this->from);
		
		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 * @param array $this->to receiver number
		 */
		$this->to = apply_filters('wp_sms_to', $this->to);
		
		/**
		 * Modify text message
		 *
		 * @since 3.4
		 * @param string $this->msg text message.
		 */
		$this->msg = apply_filters('wp_sms_msg', $this->msg);
		
		$result = $this->client->call("SendSMS", array('user' => $this->username, 'pass' => $this->password, 'fromNum' => $this->from, 'toNum' => $this->to, 'messageContent' => $this->msg, 'messageType' => 'normal'));
		
		if($result) {
			$this->InsertToDB($this->from, $this->msg, $this->to);
			
			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 * @param string $result result output.
			 */
			do_action('wp_sms_send', $result);

			return $result;
		}
		
		return new WP_Error( 'send-sms', $result );
	}

	public function GetCredit() {
		// Check username and password
		if(!$this->username && !$this->password) {
			return new WP_Error( 'account-credit', __('Username/Password does not set for this gateway', 'wp-sms') );
		}

		$result = $this->client->call("GetCredit", array('user' => $this->username, 'pass' => $this->password));
		
		return $result;
	}
}