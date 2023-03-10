<?php

class ControllerExtensionPaymentPaystation extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['lang'] = $this->session->data['language'];

        $data['return_url'] = $this->url->link('extension/payment/paystation/callback', '', 'SSL');

        return $this->load->view('extension/payment/paystation', $data);
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
        $retarr = null;
        $returnURL = urlencode($this->url->link('extension/payment/paystation/returnURL','',true));
        $postbackURL = urlencode($this->url->link('extension/payment/paystation/postback','',true));

        $postback = ($this->config->get('payment_paystation_postback') == '1');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $email = $order_info['email'];

        $reservedOrderId = $this->session->data['order_id'];

        $authenticationKey = trim($this->config->get('payment_paystation_hmac'));
        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();

        $paystationURL = "https://www.paystation.co.nz/direct/paystation.dll";
        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
        $amount = $amount * 100;
        $testMode = ($this->config->get('payment_paystation_test') == '1');

        $pstn_pi = trim($this->config->get('payment_paystation_account')); //"607113"; //Paystation ID
        $pstn_gi = trim($this->config->get('payment_paystation_gateway')); //"CARDPAY"; //Gateway ID
        if ($testMode) {
            $pstn_mr = urlencode($reservedOrderId . ':test-mode:' . $email);
        } else {
            $pstn_mr = urlencode($reservedOrderId . ':' . $email);
        } //:'.$orderID);

        $merchantSession = urlencode($reservedOrderId . '-' . microtime(true) . '-' . $this->makePaystationSessionID(18,
                18)); // max length of ms is 64 char
        $_SESSION['paystation_ms'] = $merchantSession;
        $paystationParams = "paystation&pstn_pi=" . $pstn_pi . "&pstn_gi=" . $pstn_gi . "&pstn_ms=" . $merchantSession . "&pstn_am=" . $amount . "&pstn_mr=" . $pstn_mr . "&pstn_nr=t";
        $paystationParams .= "&pstn_du=" . $returnURL;

        if ($postback) {
            $paystationParams .= "&pstn_dp=" . $postbackURL;
        }

        if ($testMode == true) {
            $paystationParams = $paystationParams . "&pstn_tm=t";
        }

        $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $paystationParams);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;
        $paystationURL .= $hmacGetParams;

        $initiationResult = $this->directTransaction($paystationURL, $paystationParams);
	$initiationResultXML  = simplexml_load_string($initiationResult);
	if (!empty($initiationResultXML)) {
		if (isset($initiationResultXML->ec) && $initiationResultXML->ec != '0') {
			$this->DisplayError($initiationResultXML->em);
		} else {
			header("Location: " . $initiationResultXML->DigitalOrder);
		}
	} else {
		$this->DisplayError("Error communicating with Paystation");
	}
    }

    public function returnURL()
    {
        $this->load->model('checkout/order');
        $transactionID = $this->request->get['ti'];
        $paystationID = trim($this->config->get('payment_paystation_account'));
        $postback = ($this->config->get('payment_paystation_postback') == '1');
        $errorCode = $this->request->get['ec'];
        $merchantSession = $this->request->get['ms'];
        if ($errorCode == '0') {
            $confirm = $this->transactionVerification($paystationID, $transactionID, $errorCode, $merchantSession);
            if ($confirm === true) {
                if (!$postback) {
                    // Successful order
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paystation_success_status_id'));
                }
                header("Location: " . ($this->url->link('checkout/success')));
            } else {
                $this->DisplayError("Error matching transaction details");
            }
        } else {
            // Failed order
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paystation_failed_status_id'));
            $this->DisplayError($this->request->get['em'] . ". The transaction did not complete.");
        }
    }

    private function transactionVerification($paystationID, $transactionID, $errorCode, $merchantSession)
    {
        $transactionVerified = false;
        $lookupXML = $this->quickLookup($paystationID, 'ti', $transactionID);
		$lookupXML = simplexml_load_string($lookupXML);
		if (!empty($lookupXML->LookupResponse)){
			$ec = $lookupXML->LookupResponse->PaystationErrorCode;
			$ms = $lookupXML->LookupResponse->MerchantSession;
			if (($errorCode == $ec) && ($merchantSession == $ms)) $transactionVerified = true;
		}
		return $transactionVerified;
    }

    private function quickLookup($pi, $type, $value)
    {
        $url = "https://payments.paystation.co.nz/lookup/"; //
        $params = "&pi=$pi&$type=$value";

        $authenticationKey = trim($this->config->get('payment_paystation_hmac'));

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
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function DisplayError($message)
    {

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

        $data['text_message'] = $message;

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

        $this->response->setOutput($this->load->view('extension/payment/paystation_failure', $data));
    }

    function postback()
    {
        $postback = ($this->config->get('payment_paystation_postback') == '1');
        if (!$postback) {
            exit("Postback not enabled in Opencart module");
        }

        $this->load->model('checkout/order');
        $xml = file_get_contents('php://input');
        $xml = simplexml_load_string($xml);
        if (!empty($xml)) {
            $errorCode = $xml->ec;
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

            $message = "Error Code: " . $errorCode . PHP_EOL;
            $message .= "Error Message: " . $errorMessage . PHP_EOL;
            $message .= "Transaction ID: " . $transactionId . PHP_EOL;
            $message .= "Card Type: " . $cardType . PHP_EOL;
            $message .= "Merchant Reference: " . $merchantReference . PHP_EOL;
            $message .= "Test Mode: " . $testMode . PHP_EOL;
            $message .= "Merchant Session: " . $merchantSession . PHP_EOL;
            $message .= "Merchant ID: " . $usedAcquirerMerchantId . PHP_EOL;
            $message .= "Amount: " . $amount . " (cents)" . PHP_EOL;
            $message .= "Transaction Time: " . $transactionTime . PHP_EOL;
            $message .= "IP: " . $requestIp . PHP_EOL;

            echo $message;

            $merchant_ref = $merchantReference;
            $xpl = explode(':', $merchant_ref);

            $orderid = $xpl[0];
            $testMode = ($this->config->get('payment_paystation_test') == '1');
            if ($orderid == "test-mode" && $xpl[1] != 'test-mode') {
                $orderid = null;
            }
            if ($errorCode == '0') {
                $paystationID = trim($this->config->get('payment_paystation_account'));
                $confirm = $this->transactionVerification($paystationID, $transactionId, $errorCode, $merchantSession);
                if ($confirm === true) {
                    // Successful order
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_paystation_success_status_id'));
                }
            } else {
                // Failed order
                $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_paystation_failed_status_id'));
            }
        }
    }
}
