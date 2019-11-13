<?php

class ControllerPaymentPaystation extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('payment/paystation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('paystation', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/paystation', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('payment/paystation', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['paystation_account'])) {
            $data['paystation_account'] = $this->request->post['paystation_account'];
        } else {
            $data['paystation_account'] = $this->config->get('paystation_account');
        }

        if (isset($this->request->post['paystation_hmac'])) {
            $data['paystation_hmac'] = $this->request->post['paystation_hmac'];
        } else {
            $data['paystation_hmac'] = $this->config->get('paystation_hmac');
        }

        if (isset($this->request->post['paystation_title'])) {
            $data['paystation_title'] = $this->request->post['paystation_title'];
        } else {
            $data['paystation_title'] = $this->config->get('paystation_title');
        }

        if (isset($this->request->post['paystation_gateway'])) {
            $data['paystation_gateway'] = $this->request->post['paystation_gateway'];
        } else {
            $data['paystation_gateway'] = $this->config->get('paystation_gateway');
        }

        if (isset($this->request->post['paystation_test'])) {
            $data['paystation_test'] = $this->request->post['paystation_test'];
        } else {
            $data['paystation_test'] = $this->config->get('paystation_test');
        }

        if (isset($this->request->post['paystation_postback'])) {
            $data['paystation_postback'] = $this->request->post['paystation_postback'];
        } else {
            $data['paystation_postback'] = $this->config->get('paystation_postback');
        }

        if (isset($this->request->post['paystation_order_status_id'])) {
            $data['paystation_order_status_id'] = $this->request->post['paystation_order_status_id'];
        } else {
            $data['paystation_order_status_id'] = $this->config->get('paystation_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['paystation_geo_zone_id'])) {
            $data['paystation_geo_zone_id'] = $this->request->post['paystation_geo_zone_id'];
        } else {
            $data['paystation_geo_zone_id'] = $this->config->get('paystation_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['paystation_status'])) {
            $data['paystation_status'] = $this->request->post['paystation_status'];
        } else {
            $data['paystation_status'] = $this->config->get('paystation_status');
        }

        if (isset($this->request->post['paystation_sort_order'])) {
            $data['paystation_sort_order'] = $this->request->post['paystation_sort_order'];
        } else {
            $data['paystation_sort_order'] = $this->config->get('paystation_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/paystation.tpl', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/paystation')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['paystation_account']) {
            $this->error['account'] = $this->language->get('error_account');
        }

        if (!$this->request->post['paystation_hmac']) {
            $this->error['hmac'] = $this->language->get('error_hmac');
        }

        if (!$this->request->post['paystation_title']) {
            $this->error['title'] = $this->language->get('error_title');
        }

        if (!$this->request->post['paystation_gateway']) {
            $this->error['gateway'] = $this->language->get('error_gateway');
        }

        return !$this->error;
    }
}
