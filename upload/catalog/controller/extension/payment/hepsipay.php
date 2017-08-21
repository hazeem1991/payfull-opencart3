<?php
class ControllerExtensionPaymentHepsipay extends Controller {

	public function index() {

		$this->language->load('extension/payment/hepsipay');

		$data['entry_hepsipay_installmet'] 	= $this->language->get('entry_hepsipay_installmet');
		$data['entry_hepsipay_amount'] 		= $this->language->get('entry_hepsipay_amount');
		$data['entry_hepsipay_total'] 		= $this->language->get('entry_hepsipay_total');

		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['month_valid'] = [];
		$data['month_valid'][] = [
			'text' => $this->language->get('entry_cc_month'),
			'value' =>''
		];

		for ($i = 1; $i <= 12; $i++) {
			$data['month_valid'][] = array(
				'text'  => sprintf('%02d', $i),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_valid'] = [];
		$data['year_valid'][] = [
			'text' => $this->language->get('entry_cc_year'),
			'value' =>''
		];
		for ($i = $today['year']; $i < $today['year'] + 17; $i++) {
			$data['year_valid'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}

		$data['entry_cc_name'] = $this->language->get('entry_cc_name');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_date'] = $this->language->get('entry_cc_date');
		$data['entry_cc_cvc'] = $this->language->get('entry_cc_cvc');

		$data['text_invalid_card'] = $this->language->get('text_invalid_card'); 
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['text_3d'] = $this->language->get('text_3d');
		$data['text_installments'] = $this->language->get('text_installments');
		$data['text_extra_installments'] = $this->language->get('text_extra_installments');
		$data['text_select_extra_inst'] = $this->language->get('text_select_extra_inst');
		$data['text_wait'] = $this->language->get('text_wait');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_one_shot'] = $this->language->get('text_one_shot');
		$data['text_bkm'] = $this->language->get('text_bkm');
		$data['text_bkm_explanation'] = $this->language->get('text_bkm_explanation');

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $base_url = $this->config->get('config_ssl');
        } else {
            $base_url = $this->config->get('config_url');
        }

        //todo: hepsipay - get bkm status
		$data['hepsipay_bkm_status']      = 0;

        $data['visa_img_path']                  = $base_url.'image/hepsipay/hepsipay_creditcard_visa.png';
        $data['master_img_path']                = $base_url.'image/hepsipay/hepsipay_creditcard_master.png';
        $data['maestro_img_path']               = $base_url.'image/hepsipay/hepsipay_creditcard_maestro.png';
        $data['troy_img_path']                  = $base_url.'image/hepsipay/hepsipay_creditcard_troy.png';
        $data['not_supported_img_path']         = $base_url.'image/hepsipay/hepsipay_creditcard_not_supported.png';
        $data['hepsipay_3dsecure_status']       = $this->config->get('hepsipay_3dsecure_status');
        $data['hepsipay_force_3dsecure_status'] = $this->config->get('hepsipay_force_3dsecure_status');
        $data['hepsipay_force_3dsecure_debit']  = 1;
        $data['hepsipay_banks_images']          = $base_url.'image/hepsipay/';
        $data['hepsipay_logo']                  = $base_url.'image/hepsipay/hepsipay-logo.png';

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$total 		= $this->currency->format($order_info['total'], $order_info['currency_code'], false, true);
		$data['total']         = $total;

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/hepsipay.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/hepsipay.tpl', $data);
		} else {
			return $this->load->view('extension/payment/hepsipay.tpl', $data);
		}
	}

	public function get_card_info(){
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/hepsipay');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_info['total'] = $this->model_extension_payment_hepsipay->getOneShotTotal($order_info['total']);
        $hepsipay_3dsecure_status 	= $this->config->get('hepsipay_3dsecure_status');
        $hepsipay_installment_status = $this->config->get('hepsipay_installment_status');

		//default data
		$defaultTotal 				=	$this->currency->format($order_info['total'], $order_info['currency_code'], false, true);
		$json 						= array();
		$json['has3d'] 				= $hepsipay_3dsecure_status;
		$json['installments'] 		= [['count' => 1, 'installment_total'=>$defaultTotal, 'total'=>$defaultTotal]];
		$json['bank_id'] 	    	= '';
		$json['card_type'] 	    	= '';

		//no cc number
		if(empty($this->request->post['cc_number']) OR !$hepsipay_installment_status){
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}

		//get info from API about bank + card + instalments
		$card_info  		 = json_decode($this->model_extension_payment_hepsipay->get_card_info(), true);
		$installments_info 	 = json_decode($this->model_extension_payment_hepsipay->getInstallments(), true);
		$bank_info 			 = array();

		//no bank is detected
		if(!isset($card_info['data']['bank_id']) Or $card_info['data']['bank_id'] == '') {
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}else{
			$json['bank_id']   = $card_info['data']['bank_id'];
			$json['card_type'] = $card_info['data']['type'];
		}

        $bank_info = [];
        if(isset($installments_info['data'])){
            foreach($installments_info['data'] as $temp) {
                if($temp['bank'] == $card_info['data']['bank_id']) {
                    $bank_info = $temp;
                }
            }
        }

        //still there is no one shot commission
        if(!count($bank_info)) {
            header('Content-type: text/json');
            echo json_encode($json);
            exit;
        }

		$oneShotTotal 				= $this->currency->format($order_info['total'], $order_info['currency_code'], false, true);
		$json['has3d'] 				= ($hepsipay_3dsecure_status)?1:0;

		//installments is not allowed for some reason
		if(!$hepsipay_installment_status){
			$json['installments'] = [['count' => 1, 'installment_total'=>$oneShotTotal, 'total'=>$oneShotTotal]];
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}


		$this->session->data['bank_id'] = $bank_info['bank'];
		$this->session->data['gateway'] = $bank_info['gateway'];
		$json['bank_id'] 				= $bank_info['bank'];

		//get info from API about extra instalments
        //todo: hepsipay - extra installments
		$extraInstallmentsAndInstallmentsArr = [];

		foreach($bank_info['installments'] as $justNormalKey=>$installment){
            if($this->config->get('hepsipay_installment_commission')){
                $commission = $installment['commission'];
                $commission = str_replace('%', '', $commission);
            }else{
                $commission = 0;
            }

			$commission = str_replace('%', '', $commission);
			$total      = $order_info['total'] + ($order_info['total'] * $commission/100);
			$total      = $this->currency->format($total, $order_info['currency_code'], false, true);
			$bank_info['installments'][$justNormalKey]['total'] = $total;

			$installment_total = ($order_info['total'] + ($order_info['total'] * $commission/100))/$installment['count'];
			$installment_total = $this->currency->format($installment_total, $order_info['currency_code'], false, true);
			$bank_info['installments'][$justNormalKey]['installment_total'] = $installment_total;

            //todo: hepsipay - extra inst
			if(false){}

		}

		$json['installments'] = array_merge(
			[
				['count' => 1, 'installment_total'=>$oneShotTotal, 'total'=>$oneShotTotal]
			],
			$bank_info['installments']
		);

        $this->session->data['installments'] = $json['installments'];

        header('Content-type: text/json');
		echo json_encode($json);
		exit;
	}

	public function get_extra_installments(){
        //todo: hepsipay - get extra installments
        $json 		        = array();
        $json['extra_inst'] = [];
        header('Content-type: text/json');
        echo json_encode($json);
        exit;
	}

	public function send(){
		$this->load->model('extension/payment/hepsipay');

		$json = array();

		$error = $this->validatePaymentData();
		if(count($error)){
			$json['error'] = $error;
			echo json_encode($json);
			exit;
		}

		$response                           = $this->model_extension_payment_hepsipay->send();
		$responseData                       = json_decode($response, true);
        $responseData['extra_installments'] = isset($responseData['extra_installments'])?$responseData['extra_installments']:0;
        $responseData['campaign_id']        = isset($responseData['campaign_id'])?$responseData['campaign_id']:0;

        if(!isset($responseData['status'])){
            $json['error']['general_error'] = 'Hepsipay gateway connection problem';
            echo json_encode($json);
            exit;
        }

        if(!isset($responseData['status']) OR !$responseData['status']){
            $json['error']['general_error'] = $responseData['ErrorMSG'];
            echo json_encode($json);
            exit;
        }

        //3d html response
        if (!isset($responseData['html']) OR $responseData['html'] == '') {
            //success
            $this->model_extension_payment_hepsipay->saveResponse($responseData);
            $this->addSubTotalForInstCommission($responseData);
            $this->model_checkout_order->addOrderHistory($responseData['passive_data'], $this->config->get('hepsipay_order_status_id'));
            $json['success'] = $this->url->link('checkout/success');
		}else{
            //success need to print html 3d response
			$this->db->query('insert into `'.DB_PREFIX.'hepsipay_3d_form` SET html="'.htmlspecialchars($responseData['html']).'"');
			$this->session->data['hepsipay_3d_form_id'] = $this->db->getLastId();
			$json['success'] = $this->url->link('extension/payment/hepsipay/secure');
		}
		echo json_encode($json);
	}

    public function addSubTotalForInstCommission($responseData){
        //no need if the installments commission is inactive
        if(!$this->config->get('hepsipay_installment_commission') OR (isset($responseData['installments']) AND $responseData['installments'] < 2)){
            return;
        }

        $this->load->model('checkout/order');
        $this->language->load('extension/payment/hepsipay');
        $installmentsCommissionFound        = false;
        $order_info                         = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $hepsipay_commission_sub_total_title = $this->language->get('commission_sub_total_title');
        $sort_order                         = 0;
        $installments_number                = 1;
        $installments_commission            = 0;

        $installments_info 	= $this->model_extension_payment_hepsipay->getInstallments();
        $installments_info  = json_decode($installments_info, true);

        if(!isset($installments_info['data'])) $installments_info['data'] = [];
        foreach($installments_info['data'] as $temp) {
            if($temp['bank'] == $responseData['bank_id']) {
                foreach($temp["installments"] as $installmentInLoop){
                    if($installmentInLoop["count"] == $responseData['installments']){
                        $installments_number         = $installmentInLoop["count"];
                        $installments_commission     = $installmentInLoop["commission"];
                        $installments_commission     = str_replace('%', '', $installments_commission);
                        $installmentsCommissionFound = true;
                        break;
                    }
                }
            }
        }

		//get extra installments
		$sql 		 = "SELECT * from `".DB_PREFIX."hepsipay_order` where order_id = '" . (int)$order_info['order_id'] . "'";
		$transaction = $this->db->query($sql)->row;
		if(isset($transaction['extra_installments']) AND $transaction['extra_installments'] != '' AND $transaction['extra_installments'] > 0){
			$installments_number .= ' +'.$transaction['extra_installments'];
		}

		if($installments_number == '1'){
			$installments_number = '';
		}else{
			$installments_number = '  ('.$installments_number.') ';
		}

        $subTotalValue = $order_info['total'] * ($installments_commission/100);
        $subTotalText  = $hepsipay_commission_sub_total_title.$installments_number.' '.$installments_commission.'% '.$this->currency->format($subTotalValue, $order_info['currency_code'], true, true);;
        $newOrderTotal = $subTotalValue + $order_info['total'];

        if($installmentsCommissionFound){
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_info['order_id'] . "', code = '" . $this->db->escape('sub_total') . "', title = '" . $this->db->escape($subTotalText) . "', `value` = '" . (float)$subTotalValue . "', sort_order = '" . (int)$sort_order . "'");
            $this->db->query("UPDATE " . DB_PREFIX . "order_total SET `value` = '" . (float)$newOrderTotal . "' WHERE order_id = '" . (int)$order_info['order_id']. "' AND code = 'total'");
            $this->db->query("UPDATE " . DB_PREFIX . "order SET `total` = '" . (float)$newOrderTotal . "' WHERE order_id = '" . (int)$order_info['order_id']. "'");
        }
    }

	public function validatePaymentData(){
		$this->language->load('extension/payment/hepsipay');
		$error = [];

		if(!isset($this->request->post['cc_name']) OR $this->request->post['cc_name'] == ''){
			$error['cc_name'] = $this->language->get('entry_cc_name').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_number']) OR $this->request->post['cc_number'] == ''){
			$error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_month']) OR $this->request->post['cc_month'] == ''){
			$error['cc_month'] = $this->language->get('entry_cc_month').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_year']) OR $this->request->post['cc_year'] == ''){
			$error['cc_year'] = $this->language->get('entry_cc_year').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_cvc']) OR $this->request->post['cc_cvc'] == ''){
			$error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_cvc']) OR $this->request->post['cc_cvc'] == ''){
			$error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_required');
		}

        //------------------------------------
        if(isset($this->request->post['cc_number']) AND !is_numeric($this->request->post['cc_number']) ){
            $error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_cvc']) AND !is_numeric($this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_is_not_number');
        }

        //------------------------------------
        if(isset($this->request->post['cc_number']) AND !is_numeric($this->request->post['cc_number']) ){
            $error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_number']) AND $this->checkCCNumber($this->request->post['cc_number']) == ''){
            $error['cc_number'] = $this->language->get('entry_cc_not_supported');
        }
        if(isset($this->request->post['cc_cvc']) AND !is_numeric($this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_cvc']) AND  !$this->checkCCCVC($this->request->post['cc_number'], $this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_cc_cvc_wrong');
        }
        if(isset($this->request->post['cc_month']) AND isset($this->request->post['cc_year']) AND !$this->checkCCEXPDate($this->request->post['cc_month'], $this->request->post['cc_year']) ){
            $error['cc_year'] = $this->language->get('entry_cc_date_wrong');
            $error['cc_month'] = $this->language->get('entry_cc_date_wrong');
        }

        if(isset($this->request->post['use3d']) AND $this->request->post['use3d'] AND !$this->config->get('hepsipay_3dsecure_status')){
            if(!$this->config->get('hepsipay_force_3dsecure_debit')){
                $error['general_error'] = $this->language->get('entry_3d_not_available');
            }
        }

		if(isset($this->request->post['useBKM']) AND $this->request->post['useBKM'] AND !$this->config->get('hepsipay_bkm_status')){
			$error['general_error'] = $this->language->get('entry_bkm_not_available');
		}

		if(isset($this->request->post['useBKM']) AND $this->request->post['useBKM'] AND $this->config->get('hepsipay_bkm_status')){
			unset($error['cc_name']);
			unset($error['cc_number']);
			unset($error['cc_cvc']);
			unset($error['cc_month']);
			unset($error['cc_year']);
		}

		return $error;
	}

    public function checkCCNumber($cardNumber){
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $len = strlen($cardNumber);
        if ($len < 15 || $len > 16) {
            return '';
        }else {
            switch($cardNumber) {
                case(preg_match ('/^4/', $cardNumber) >= 1):
                    return 'VISA';
                    break;
                case(preg_match ('/^5[1-5]/', $cardNumber) >= 1):
                    return 'MASTERCARD';
                    break;
                case(preg_match ('/^6', $cardNumber) >= 1):
                    return 'MAESTRO';
                    break;
                case(preg_match ('/^9/', $cardNumber) >= 1):
                    return 'TROY';
                    break;
                default:
                    return '';
                    break;
            }
        }
    }

    public function checkCCCVC($cardNumber, $cvc){
        // Get the first number of the credit card so we know how many digits to look for
        $firstnumber = (int) substr($cardNumber, 0, 1);
        if ($firstnumber === 3){
            if (!preg_match("/^\d{4}$/", $cvc)){
                // The credit card is an American Express card but does not have a four digit CVV code
                return false;
            }
        }
        else if (!preg_match("/^\d{3}$/", $cvc)){
            // The credit card is a Visa, MasterCard, or Discover Card card but does not have a three digit CVV code
            return false;
        }
        return true;
    }

    public function checkCCEXPDate($month, $year){
        if(strtotime('30-'.$month.'-'.$year) <= time()){
            return false;
        }
        return true;
    }

    public function secure(){
        try{
            $html = $this->db->query('select html from `'.DB_PREFIX.'hepsipay_3d_form` WHERE hepsipay_3d_form_id = "'.$this->session->data['hepsipay_3d_form_id'].'"');
            $html = isset($html->row['html'])?$html->row['html']:'Bad Request';
            //delete form
            $this->db->query('delete from `'.DB_PREFIX.'hepsipay_3d_form` WHERE hepsipay_3d_form_id = "'.$this->session->data['hepsipay_3d_form_id'].'"');
            echo htmlspecialchars_decode($html);
        } catch (Exception $e){
            echo 'Bad Request';
        }
    }

	public function callback() {
		$this->load->model('extension/payment/hepsipay');

        $post = $this->request->post;

		//hash
		$merchantPassword = $this->config->get('hepsipay_password');
		$hash             = self::generateHash($post, $merchantPassword);

		//extra installments
        $post['extra_installments'] = isset($post['extra_installments'])?$post['extra_installments']:0;
        $post['campaign_id']        = isset($post['campaign_id'])?$post['campaign_id']:0;

		//save response 
		$this->model_extension_payment_hepsipay->saveResponse($post);

		if (isset($post['passive_data'])) {
			$order_id = $post['passive_data'];
		} else {
			$order_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);
		if ($order_info && $post['ErrorCode'] == '00' && ($hash == $post["hash"])) {
			$responseData =  $post;
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('hepsipay_order_status_id'));
			$this->addSubTotalForInstCommission($responseData);
			$this->response->redirect($this->url->link('checkout/success'));
		}else{
			$this->response->redirect($this->url->link('checkout/failure'));
		}
	}

    protected static function generateHash($params, $password){
        $arr = [];
        if(isset($params['hash']))  unset($params['hash']);
        if(isset($params['_csrf'])) unset($params['_csrf']);

        foreach($params as $param_key=>$param_val){$arr[strtolower($param_key)]=$param_val;}
        ksort($arr);
        $hashString_char_count = "";
		foreach ($arr as $key=>$val) {
		    $l =  mb_strlen($val);
		    if($l) $hashString_char_count .= $l . $val;
		}
		$hashString_char_count      = strtolower(hash_hmac("sha1", $hashString_char_count, $password));
		return $hashString_char_count;
	}
}