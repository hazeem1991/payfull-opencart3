<?php
class ControllerExtensionPaymentPayfull extends Controller {
	private $error = array();

	public function index() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);


        $this->load->language('extension/payment/payfull');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('payfull', $this->request->post);
			//for opencart 3
			$this->model_setting_setting->editSetting('payment_payfull', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
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
        //todo: payfull - extra inst
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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/payfull', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/payfull', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payfull_endpoint'])) {
			$data['payfull_endpoint'] = $this->request->post['payfull_endpoint'];
		} else {
			$data['payfull_endpoint'] = $this->config->get('payfull_endpoint');
		}

		if (isset($this->request->post['payfull_3dsecure_status'])) {
			$data['payfull_3dsecure_status'] = $this->request->post['payfull_3dsecure_status'];
		} else {
			$data['payfull_3dsecure_status'] = $this->config->get('payfull_3dsecure_status');
		}

        if (isset($this->request->post['payfull_force_3dsecure_status'])) {
            $data['payfull_force_3dsecure_status'] = $this->request->post['payfull_force_3dsecure_status'];
        } else {
            $data['payfull_force_3dsecure_status'] = $this->config->get('payfull_force_3dsecure_status');
        }

        if (isset($this->request->post['payfull_force_3dsecure_debit'])) {
            $data['payfull_force_3dsecure_debit'] = $this->request->post['payfull_force_3dsecure_debit'];
        } else {
            $data['payfull_force_3dsecure_debit'] = $this->config->get('payfull_force_3dsecure_debit');
        }
        $data['payfull_force_3dsecure_debit'] = true;

		if (isset($this->request->post['payfull_installment_status'])) {
			$data['payfull_installment_status'] = $this->request->post['payfull_installment_status'];
		} else {
			$data['payfull_installment_status'] = $this->config->get('payfull_installment_status');
		}

        if (isset($this->request->post['payfull_installment_commission'])) {
            $data['payfull_installment_commission'] = $this->request->post['payfull_installment_commission'];
        } else {
            $data['payfull_installment_commission'] = $this->config->get('payfull_installment_commission');
        }

        //todo: payfull - extra inst
        $data['payfull_extra_installment_status'] = 0;


		if (isset($this->request->post['payfull_bkm_status'])) {
			$data['payfull_bkm_status'] = $this->request->post['payfull_bkm_status'];
		} else {
			$data['payfull_bkm_status'] = $this->config->get('payfull_bkm_status');
		}

		if (isset($this->request->post['payfull_username'])) {
			$data['payfull_username'] = $this->request->post['payfull_username'];
		} else {
			$data['payfull_username'] = $this->config->get('payfull_username');
		}

		if (isset($this->request->post['payfull_password'])) {
			$data['payfull_password'] = $this->request->post['payfull_password'];
		} else {
			$data['payfull_password'] = $this->config->get('payfull_password');
		}

		if (isset($this->request->post['payfull_total'])) {
			$data['payfull_total'] = $this->request->post['payfull_total'];
		} else {
			$data['payfull_total'] = $this->config->get('payfull_total');
		}

		if (isset($this->request->post['payfull_order_status_id'])) {
			$data['payfull_order_status_id'] = $this->request->post['payfull_order_status_id'];
		} else {
			$data['payfull_order_status_id'] = $this->config->get('payfull_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payfull_geo_zone_id'])) {
			$data['payfull_geo_zone_id'] = $this->request->post['payfull_geo_zone_id'];
		} else {
			$data['payfull_geo_zone_id'] = $this->config->get('payfull_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_payfull_status'])) {
			$data['payment_payfull_status'] = $this->request->post['payment_payfull_status'];
		} else {
			$data['payment_payfull_status'] = $this->config->get('payment_payfull_status');
		}

		if (isset($this->request->post['payfull_sort_order'])) {
			$data['payfull_sort_order'] = $this->request->post['payfull_sort_order'];
		} else {
			$data['payfull_sort_order'] = $this->config->get('payfull_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/payfull', $data));
	}

	public function install() {
		$this->load->model('extension/payment/payfull');
		$this->model_extension_payment_payfull->install();
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/payfull')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        return !$this->error;
	}
}