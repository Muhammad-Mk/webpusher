<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class SubscriptionController extends CI_Controller {

	public function __construct(){
        parent::__construct();
        $this->load->model('Subscription_modal');
	}
	
	public function getAllSubscriptions($site_id){
		$subscriptions = $this->Subscription_modal->allSubscription($site_id);
		$this->load->view('all_subscriptions', ['subscriptions' => $subscriptions, 'site_id' => $site_id]);
	}

    public function save() {
		// POST data
		$postData = json_decode(trim(file_get_contents('php://input')), true); //$this->input->post('endpoint');
		$data = $this->Subscription_modal->saveSubscription($postData['subscription'], $postData['site_key']);
		if($data){
			echo json_encode(array('status' => 'TRUE', 'message' => 'Subscription saved'));
		}
		else{
			echo json_encode(array('status' => 'FALSE', 'message' => 'Something went wrong, Please check your configuration'));
		}
	}

	public function pushNotification(){
		$received_data = $this->security->xss_clean($this->input->post());
		$subscription_detail = $this->Subscription_modal->getSpecificSubscription($received_data);
		// print_r($subscription_detail); return;
		
		if(empty($subscription_detail)){
			return json_encode(array('status' => 'FALSE', 'message' => 'No Subscription Detail found'));
		}

		/*
			$site_id = $subscription_detail[0]->site_id;
			$endpoint = $subscription_detail[0]->endpoint;
			$p256dh = $subscription_detail[0]->p256dh;
			$auth = $subscription_detail[0]->auth;
			$expirationTime = $subscription_detail[0]->expirationTime;

			$public_key = $subscription_detail[0]->public_key;
			$private_key = $subscription_detail[0]->private_key;
			$vapidKeys = [
				'publicKey' => $public_key,
				'privateKey' => $private_key
			];
			
			$subscription_data = [
				// 'contentEncoding' => "aes128gcm",
				'endpoint' => $endpoint,
				'expirationTime' => $expirationTime,
				'keys' => [
					'auth' => $auth,
					'p256dh' => $p256dh
				]
			];
			$subscription = Subscription::create($subscription_data);
			$auth = [
				'VAPID' => [
					'subject' => 'muhammad.mk5698@gmail.com',
					'publicKey' => $public_key,
					'privateKey' => $private_key
				],
			];
			
			$webPush = new WebPush($auth);
			$report = $webPush->sendOneNotification(
				$subscription,
				"Hello! 👋"
			);
			// handle eventual errors here, and remove the subscription from your server if it is expired
			$endpoint = $report->getRequest()->getUri()->__toString();
			if ($report->isSuccess()) {
				echo "[v] Message sent successfully for subscription {$endpoint}.";
			} else {
				echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
			}
			return;
		*/


		$auth = [
			'VAPID' => [
				'subject' => 'muhammad.mk5698@gmail.com',
				'publicKey' => $subscription_detail[0]->public_key,
				'privateKey' => $subscription_detail[0]->private_key
			],
		];
		$defaultOptions = [
			'TTL' => 300, // defaults to 4 weeks
			'urgency' => 'normal', // protocol defaults to "normal"
			'topic' => 'new_event', // not defined by default,
			'batchSize' => 200, // defaults to 1000
		];

		// array of notifications
		$notifications = [];
		foreach($subscription_detail as $key => $subscriptions){
			$subscription_data = [
				'contentEncoding' => "aes128gcm",
				'endpoint' => $subscriptions->endpoint,
				'expirationTime' => $subscriptions->expirationTime,
				'keys' => [
					'auth' => $subscriptions->auth,
					'p256dh' => $subscriptions->p256dh
				]
			];

			$temp_notifications = [
				'subscription' => Subscription::create($subscription_data),
				'payload' => "Hello! 👋",
			];
			$notifications[] = $temp_notifications;
		}

		$webPush = new WebPush($auth, $defaultOptions);
		// send multiple notifications with payload
		foreach ($notifications as $key => $notification) {
			$webPush->queueNotification(
				$notification['subscription'],
				$notification['payload']
			);
		}

		/**
		 * Check sent results
		 * @var MessageSentReport $report
		 */
		$sent_end_points=[];
		$not_sent_end_points=[];
		$not_sent_end_points_detail=[];
		foreach ($webPush->flush() as $report) {
			// print_r($report->jsonSerialize()); echo "<br><br><br>";
			
			$endpoint = $report->getRequest()->getUri()->__toString();

			if ($report->isSuccess()) {
				$sent_end_points[] = $endpoint;
				echo "sent successfully for subscription.<br>";
			} else {
				$not_sent_end_points[] = $endpoint;
				$not_sent_end_points_detail[] = ['endpoint' => $endpoint, 'reason' => $report->getReason()];
				echo "failed to sent for subscription. <br>"; // .$report->getReason();
			}
			/*$requestToPushService = $report->getRequest();
			$responseOfPushService = $report->getResponse();
			$failReason = $report->getReason();
			$isTheEndpointWrongOrExpired = $report->isSubscriptionExpired();
			echo "<br><br>requestToPushService: "; print_r($requestToPushService); echo "<br>";
			echo "responseOfPushService: "; print_r($responseOfPushService); echo "<br>";
			echo "failReason: "; print_r($failReason); echo "<br>";
			echo "isTheEndpointWrongOrExpired: "; print_r($isTheEndpointWrongOrExpired); echo "<br>";*/
		}
		print_r($this->Subscription_modal->saveFailToSentSubscriptions($not_sent_end_points_detail));
		print_r($this->Subscription_modal->markSubscriptionsAsDelete($not_sent_end_points));
		// print_r($not_sent_end_points);

	}

}
