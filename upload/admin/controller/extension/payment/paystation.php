<?php

class ControllerExtensionPaymentPaystation extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/paystation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_paystation', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment/paystation', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        $data['entry_account'] = $this->language->get('entry_account');
        $data['entry_gateway'] = $this->language->get('entry_gateway');
        $data['entry_hmac'] = $this->language->get('entry_hmac');
        $data['entry_test'] = $this->language->get('entry_test');
        $data['entry_title'] = $this->language->get('entry_title');
        $data['entry_postback'] = $this->language->get('entry_postback');
        $data['entry_postback'] = $this->language->get('entry_postback');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_title_order_status_tab'] = $this->language->get('entry_title_order_status_tab');
        $data['entry_title_settings_tab'] = $this->language->get('entry_title_settings_tab');
        $data['entry_success_status'] = $this->language->get('entry_success_status');
        $data['entry_failed_status'] = $this->language->get('entry_failed_status');

        $data['help_hmac'] = $this->language->get('help_hmac');
        $data['help_postback'] = $this->language->get('help_postback');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['account'])) {
            $data['error_account'] = $this->error['account'];
        } else {
            $data['error_account'] = '';
        }

        if (isset($this->error['hmac'])) {
            $data['error_hmac'] = $this->error['hmac'];
        } else {
            $data['error_hmac'] = '';
        }

        if (isset($this->error['gateway'])) {
            $data['error_gateway'] = $this->error['gateway'];
        } else {
            $data['error_gateway'] = '';
        }

        if (isset($this->error['title'])) {
            $data['error_title'] = $this->error['title'];
        } else {
            $data['error_title'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/paystation', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/payment/paystation', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        if (isset($this->request->post['payment_paystation_account'])) {
            $data['payment_paystation_account'] = $this->request->post['payment_paystation_account'];
        } else {
            $data['payment_paystation_account'] = $this->config->get('payment_paystation_account');
        }

        if (isset($this->request->post['payment_paystation_hmac'])) {
            $data['payment_paystation_hmac'] = $this->request->post['payment_paystation_hmac'];
        } else {
            $data['payment_paystation_hmac'] = $this->config->get('payment_paystation_hmac');
        }

        if (isset($this->request->post['payment_paystation_title'])) {
            $data['payment_paystation_title'] = $this->request->post['payment_paystation_title'];
        } else {
            $data['payment_paystation_title'] = $this->config->get('payment_paystation_title');
        }

        if (isset($this->request->post['payment_paystation_gateway'])) {
            $data['payment_paystation_gateway'] = $this->request->post['payment_paystation_gateway'];
        } else {
            $data['payment_paystation_gateway'] = $this->config->get('payment_paystation_gateway');
        }

        if (isset($this->request->post['payment_paystation_test'])) {
            $data['payment_paystation_test'] = $this->request->post['payment_paystation_test'];
        } else {
            $data['payment_paystation_test'] = $this->config->get('payment_paystation_test');
        }

        if (isset($this->request->post['payment_paystation_postback'])) {
            $data['payment_paystation_postback'] = $this->request->post['payment_paystation_postback'];
        } else {
            $data['payment_paystation_postback'] = $this->config->get('payment_paystation_postback');
        }

        if (isset($this->request->post['payment_paystation_order_status_id'])) {
            $data['payment_paystation_order_status_id'] = $this->request->post['payment_paystation_order_status_id'];
        } else {
            $data['payment_paystation_order_status_id'] = $this->config->get('payment_paystation_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_paystation_success_status_id'])) {
            $data['payment_paystation_success_status_id'] = $this->request->post['payment_paystation_success_status_id'];
        } else {
            $data['payment_paystation_success_status_id'] = $this->config->get('payment_paystation_success_status_id');
        }

        if (isset($this->request->post['payment_paystation_failed_status_id'])) {
            $data['payment_paystation_failed_status_id'] = $this->request->post['payment_paystation_failed_status_id'];
        } else {
            $data['payment_paystation_failed_status_id'] = $this->config->get('payment_paystation_failed_status_id');
        }

        if (isset($this->request->post['payment_paystation_geo_zone_id'])) {
            $data['payment_paystation_geo_zone_id'] = $this->request->post['payment_paystation_geo_zone_id'];
        } else {
            $data['payment_paystation_geo_zone_id'] = $this->config->get('payment_paystation_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_paystation_status'])) {
            $data['payment_paystation_status'] = $this->request->post['payment_paystation_status'];
        } else {
            $data['payment_paystation_status'] = $this->config->get('payment_paystation_status');
        }

        if (isset($this->request->post['payment_paystation_sort_order'])) {
            $data['payment_paystation_sort_order'] = $this->request->post['payment_paystation_sort_order'];
        } else {
            $data['payment_paystation_sort_order'] = $this->config->get('payment_paystation_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paystation', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/paystation')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_paystation_account']) {
            $this->error['account'] = $this->language->get('error_account');
        }

        if (!$this->request->post['payment_paystation_hmac']) {
            $this->error['hmac'] = $this->language->get('error_hmac');
        }

        if (!$this->request->post['payment_paystation_gateway']) {
            $this->error['gateway'] = $this->language->get('error_gateway');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/payment/paystation');
        $this->model_extension_payment_paystation->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/paystation');
        $this->model_extension_payment_paystation->uninstall();
    }
}
