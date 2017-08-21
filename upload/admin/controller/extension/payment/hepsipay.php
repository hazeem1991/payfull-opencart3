<?php
class ControllerExtensionPaymentHepsipay extends Controller {
	private $error = array();

	public function index() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);


        $this->load->language('extension/payment/hepsipay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('hepsipay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_yes'] = $this->language->get('entry_yes');
		$data['entry_no'] = $this->language->get('entry_no');

		$data['entry_endpoint'] = $this->language->get('entry_endpoint');
		$data['entry_username'] = $this->language->get('entry_username');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['entry_3dsecure_status'] = $this->language->get('entry_3dsecure_status');
		$data['entry_force_3dsecure_status'] = $this->language->get('entry_force_3dsecure_status');
		$data['entry_force_3dsecure_debit'] = $this->language->get('entry_force_3dsecure_debit');
        $data['entry_force_3dsecure_hint'] = $this->language->get('entry_force_3dsecure_hint');
        $data['entry_installment_status'] = $this->language->get('entry_installment_status');
        $data['entry_installment_commission'] = $this->language->get('entry_installment_commission');
        //todo: hepsipay - extra inst
		$data['entry_extra_installment_status'] = 0;
		$data['entry_bkm_status'] = $this->language->get('entry_bkm_status');
		$data['entry_check_merchant'] = $this->language->get('entry_check_merchant');

		$data['help_total'] = $this->language->get('help_total');
        $data['entry_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/hepsipay', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['action'] = $this->url->link('extension/payment/hepsipay', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');

		if (isset($this->request->post['hepsipay_endpoint'])) {
			$data['hepsipay_endpoint'] = $this->request->post['hepsipay_endpoint'];
		} else {
			$data['hepsipay_endpoint'] = $this->config->get('hepsipay_endpoint');
		}
        $data['hepsipay_endpoint'] = 'https://pluginmanager.hepsipay.com/portal/web/api/v1';

		if (isset($this->request->post['hepsipay_3dsecure_status'])) {
			$data['hepsipay_3dsecure_status'] = $this->request->post['hepsipay_3dsecure_status'];
		} else {
			$data['hepsipay_3dsecure_status'] = $this->config->get('hepsipay_3dsecure_status');
		}

        if (isset($this->request->post['hepsipay_force_3dsecure_status'])) {
            $data['hepsipay_force_3dsecure_status'] = $this->request->post['hepsipay_force_3dsecure_status'];
        } else {
            $data['hepsipay_force_3dsecure_status'] = $this->config->get('hepsipay_force_3dsecure_status');
        }

        if (isset($this->request->post['hepsipay_force_3dsecure_debit'])) {
            $data['hepsipay_force_3dsecure_debit'] = $this->request->post['hepsipay_force_3dsecure_debit'];
        } else {
            $data['hepsipay_force_3dsecure_debit'] = $this->config->get('hepsipay_force_3dsecure_debit');
        }
        $data['hepsipay_force_3dsecure_debit'] = true;

		if (isset($this->request->post['hepsipay_installment_status'])) {
			$data['hepsipay_installment_status'] = $this->request->post['hepsipay_installment_status'];
		} else {
			$data['hepsipay_installment_status'] = $this->config->get('hepsipay_installment_status');
		}

        if (isset($this->request->post['hepsipay_installment_commission'])) {
            $data['hepsipay_installment_commission'] = $this->request->post['hepsipay_installment_commission'];
        } else {
            $data['hepsipay_installment_commission'] = $this->config->get('hepsipay_installment_commission');
        }

        //todo: hepsipay - extra inst
        $data['hepsipay_extra_installment_status'] = 0;


		if (isset($this->request->post['hepsipay_bkm_status'])) {
			$data['hepsipay_bkm_status'] = $this->request->post['hepsipay_bkm_status'];
		} else {
			$data['hepsipay_bkm_status'] = $this->config->get('hepsipay_bkm_status');
		}

		if (isset($this->request->post['hepsipay_username'])) {
			$data['hepsipay_username'] = $this->request->post['hepsipay_username'];
		} else {
			$data['hepsipay_username'] = $this->config->get('hepsipay_username');
		}

		if (isset($this->request->post['hepsipay_password'])) {
			$data['hepsipay_password'] = $this->request->post['hepsipay_password'];
		} else {
			$data['hepsipay_password'] = $this->config->get('hepsipay_password');
		}

		if (isset($this->request->post['hepsipay_total'])) {
			$data['hepsipay_total'] = $this->request->post['hepsipay_total'];
		} else {
			$data['hepsipay_total'] = $this->config->get('hepsipay_total');
		}

		if (isset($this->request->post['hepsipay_order_status_id'])) {
			$data['hepsipay_order_status_id'] = $this->request->post['hepsipay_order_status_id'];
		} else {
			$data['hepsipay_order_status_id'] = $this->config->get('hepsipay_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['hepsipay_geo_zone_id'])) {
			$data['hepsipay_geo_zone_id'] = $this->request->post['hepsipay_geo_zone_id'];
		} else {
			$data['hepsipay_geo_zone_id'] = $this->config->get('hepsipay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['hepsipay_status'])) {
			$data['hepsipay_status'] = $this->request->post['hepsipay_status'];
		} else {
			$data['hepsipay_status'] = $this->config->get('hepsipay_status');
		}

		if (isset($this->request->post['hepsipay_sort_order'])) {
			$data['hepsipay_sort_order'] = $this->request->post['hepsipay_sort_order'];
		} else {
			$data['hepsipay_sort_order'] = $this->config->get('hepsipay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/hepsipay', $data));
	}

	public function install() {
		$this->load->model('extension/payment/hepsipay');
		$this->model_extension_payment_hepsipay->install();
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/hepsipay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}