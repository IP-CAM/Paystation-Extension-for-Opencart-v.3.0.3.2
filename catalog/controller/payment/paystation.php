<?php

class ControllerPaymentPaystation extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['lang'] = $this->session->data['language'];

        $data['return_url'] = $this->url->link('payment/paystation/callback', '', 'SSL');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paystation.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/paystation.tpl', $data);
        } else {
            return $this->load->view('/template/payment/paystation.tpl', $data);
        }
    }

    public function initiate_payment()
    {
        $this->initiate_paystation();
    }

    private function makePaystationSessionID($min = 8, $max = 8)
    {
        // seed the random number generator - straight from PHP manual
        $seed = (double)time() * getrandmax();
        srand($seed);

        // make a string of $max characters with ASCII values of 40-122
        $p = 0;
        $pass = "";
        while ($p < $max):
            $r = chr(123 - (rand() % 75));

            // get rid of all non-alphanumeric characters
            if (!($r >= 'a' && $r <= 'z') && !($r >= 'A' && $r <= 'Z') && !($r >= '1' && $r <= '9')) {
                continue;
            }
            $pass .= $r;

            $p++;
        endwhile;
        // if string is too short, remake it
        if (strlen($pass) < $min):
            $pass = $this->makePaystationSessionID($min, $max);
        endif;

        return $pass;
    }

    private function directTransaction($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }

    private function initiate_paystation()
    {
        $returnURL = urlencode($this->url->link('payment/paystation/returnURL'));
        $postbackURL = urlencode($this->url->link('payment/paystation/postback'));

        $postback = ($this->config->get('paystation_postback') == '1');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $email = $order_info['email'];

        $reservedOrderId = $this->session->data['order_id'];

        $authenticationKey = trim($this->config->get('paystation_hmac'));
        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();

        $paystationURL = "https://www.paystation.co.nz/direct/paystation.dll";
        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
        $amount = $amount * 100;
        $testMode = ($this->config->get('paystation_test') == '1');

        $pstn_pi = trim($this->config->get('paystation_account')); //"607113"; //Paystation ID
        $pstn_gi = trim($this->config->get('paystation_gateway')); //"CARDPAY"; //Gateway ID
        if ($testMode)
            $pstn_mr = urlencode($reservedOrderId . ':test-mode:' . $email);
        else
            $pstn_mr = urlencode($reservedOrderId . ':' . $email); //:'.$orderID);

        $merchantSession = urlencode($reservedOrderId . '-' . microtime(true) . '-' . $this->makePaystationSessionID(18, 18)); // max length of ms is 64 char 
        $_SESSION['paystation_ms'] = $merchantSession;
        $paystationParams = "paystation&pstn_pi=" . $pstn_pi . "&pstn_gi=" . $pstn_gi . "&pstn_ms=" . $merchantSession . "&pstn_am=" . $amount . "&pstn_mr=" . $pstn_mr . "&pstn_nr=t";
        $paystationParams .= "&pstn_du=" . $returnURL;

        if ($postback)
            $paystationParams .= "&pstn_dp=" . $postbackURL;

        if ($testMode == true) {
            $paystationParams = $paystationParams . "&pstn_tm=t";
        }

        $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $paystationParams);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;
        $paystationURL .= $hmacGetParams;

        $initiationResult = $this->directTransaction($paystationURL, $paystationParams);
        preg_match_all("/<(.*?)>(.*?)\</", $initiationResult, $outarr, PREG_SET_ORDER);
        $n = 0;
        while (isset($outarr[$n])) {
            $retarr[$outarr[$n][1]] = strip_tags($outarr[$n][0]);
            $n++;
        }
        header("Location: " . $retarr['DigitalOrder']);
    }

    public function returnURL()
    {
        $QL_amount = '';
        $QL_EC = '';
        $QL_merchant_session = '';
        $this->load->model('checkout/order');
        $transactionID = $this->request->get['ti'];
        $paystationID = trim($this->config->get('paystation_account'));
        $postback = ($this->config->get('paystation_postback') == '1');
        if ($this->request->get['ec'] == 0) {
            $confirm = $this->transactionVerification($paystationID, $transactionID, $QL_amount, $QL_merchant_session, $QL_EC);
            if ((int)$confirm == 0 && ($QL_amount) == ($this->request->get['am']) && $this->request->get['ms'] == $QL_merchant_session) {
                if (!$postback) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('paystation_order_status_id'));
                }
                header("Location: " . ($this->url->link('checkout/success')));
            } else {
                $this->DisplayError("Error matching transaction details");
            }
        } else {

            $this->DisplayError($this->request->get['em'] . ". The transaction did not complete.");
        }
    }

    private function transactionVerification($paystationID, $transactionID, &$QL_amount, &$QL_merchant_session, &$QL_EC)
    {
        $transactionVerified = '';
        $lookupXML = $this->quickLookup($paystationID, 'ti', $transactionID);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $lookupXML, $vals, $tags);
        xml_parser_free($p);
        for ($i = 0; $i < count($vals); $i++) {
            $key = $vals[$i];
            $key = $key['tag'];
            $val = $i;
            if ($key == "PAYSTATIONERRORCODE") {
                $transactionVerified = (int)$vals[$val]['value'];
                $QL_EC = (int)$transactionVerified;
                //echo "QL_EC: "; var_dump ($QL_EC);
            } elseif ($key == "PURCHASEAMOUNT") { //19
                $QL_amount = $vals[$val];
                $QL_amount = $QL_amount ['value'];
            } elseif ($key == "MERCHANTSESSION") { //15
                $QL_merchant_session = $vals[$val]['value'];
            } else {
                continue;
            }
        }
        return $transactionVerified;
    }

    private function quickLookup($pi, $type, $value)
    {
        $url = "https://payments.paystation.co.nz/lookup/"; //
        $params = "&pi=$pi&$type=$value";

        $authenticationKey = trim($this->config->get('paystation_hmac'));

        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();

        $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $params);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;

        $url .= $hmacGetParams;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        if (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function parseCode($mvalues)
    {
        $result = '';
        for ($i = 0; $i < count($mvalues); $i++) {
            if (!strcmp($mvalues[$i]["tag"], "QSIRESPONSECODE") && isset($mvalues[$i]["value"])) {
                $result = $mvalues[$i]["value"];
            }
        }
        return $result;
    }

    private function DisplayError($message)
    {
        $errorMsg = "<div class=\"alert alert-danger\">
            <i class=\"fa fa-exclamation-circle\"></i>
            " . $message . ".
            <button class=\"close\" data-dismiss=\"alert\" type=\"button\">×</button>
            </div>";

        $this->load->language('checkout/failure');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_basket'),
            'href' => $this->url->link('checkout/cart')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_failure'),
            'href' => $this->url->link('checkout/failure')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_message'] = $errorMsg;

        $data['text_message'] .= "<p>If the problem persists please contact us
            with the details of the order you are trying to place.</p>";

        $data['button_continue'] = $this->language->get('button_continue');

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/common/success.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/common/success.tpl', $data));
        }
    }

    function postback()
    {
        $postback = ($this->config->get('paystation_postback') == '1');
        if (!$postback)
            exit("Postback not enabled in Opencart module");

        $this->load->model('checkout/order');
        $xml = file_get_contents('php://input');
        $xml = simplexml_load_string($xml);

        if (!empty($xml)) {
            $errorCode = (int)$xml->ec;
            $errorMessage = $xml->em;
            $transactionId = $xml->ti;
            $cardType = $xml->ct;
            $merchantReference = $xml->merchant_ref;
            $testMode = $xml->tm;
            $merchantSession = $xml->MerchantSession;
            $usedAcquirerMerchantId = $xml->UsedAcquirerMerchantID;
            $amount = $xml->PurchaseAmount; // Note this is in cents
            $transactionTime = $xml->TransactionTime;
            $requestIp = $xml->RequestIP;

            $message = "Error Code: " . $errorCode . "<br/>";
            $message .= "Error Message: " . $errorMessage . "<br/>";
            $message .= "Transaction ID: " . $transactionId . "<br/>";
            $message .= "Card Type: " . $cardType . "<br/>";
            $message .= "Merchant Reference: " . $merchantReference . "<br/>";
            $message .= "Test Mode: " . $testMode . "<br/>";
            $message .= "Merchant Session: " . $merchantSession . "<br/>";
            $message .= "Merchant ID: " . $usedAcquirerMerchantId . "<br/>";
            $message .= "Amount: " . $amount . " (cents)<br/>";
            $message .= "Transaction Time: " . $transactionTime . "<br/>";
            $message .= "IP: " . $requestIp . "<br/>";

            echo $message;

            $merchant_ref = $merchantReference;
            $xpl = explode(':', $merchant_ref);

            $orderid = $xpl[0];
            $testMode = ($this->config->get('paystation_test') == '1');
            if ($orderid == "test-mode" && $xpl[1] != 'test-mode') {
                $orderid = NULL;
            }

            if ($errorCode == 0) {
                $QL_amount = '';
                $QL_EC = '';
                $QL_merchant_session = '';
                $paystationID = trim($this->config->get('paystation_account'));
                $confirm = $this->transactionVerification($paystationID, $transactionId, $QL_amount, $QL_merchant_session, $QL_EC);
                if ((int)$confirm == 0 && ($QL_amount) == ($amount) && $merchantSession == $QL_merchant_session) {
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('paystation_order_status_id'));
                }
            }
        }
    }

    // Function not implemented / utilised
    public function postresponse()
    {
        $this->load->model('checkout/order');

        $postdata = file_get_contents("php://input");

        $xml = simplexml_load_string($postdata);

        if (isset($xml->MerchantSession)) {
            $pieces = explode("-", $xml->MerchantSession);
            $order_id = $pieces[2];
        } else {
            $order_id = 0;
        }

        if ($xml->ec == '0') {
            $this->model_checkout_order->confirm($order_id, $this->config->get('paystation_order_status_id'));
        } else {
            // set order_status_id to 10 (failed)
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '10', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '10', notify = '1', comment = '" . $this->db->escape(($comment && $notify) ? $comment : '') . "', date_added = NOW()");
        }
    }
}
