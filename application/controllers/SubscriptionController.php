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
		// $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
		// $request = json_decode($stream_clean);
		// echo "request: "; print_r($request);

		$post_data = json_decode(trim(file_get_contents('php://input')), true);
		// echo "data: "; print_r($post_data['subscription']); return;
		$data = $this->Subscription_modal->saveSubscription($post_data['subscription'], $post_data['site_key']);
		// echo "data result: "; print_r($data); return;
		if($data){
			echo json_encode(array('status' => 'TRUE', 'message' => 'Subscription saved'));
		}
		else{
			echo json_encode(array('status' => 'FALSE', 'message' => 'Something went wrong, Please check your configuration'));
		}
	}

	public function sendPushNotification(){
		$received_data = $this->security->xss_clean($this->input->post());
		$campaign_id = $this->Subscription_modal->getCampainIdAfterSavingCampain($received_data);
		$subscription_detail = $this->Subscription_modal->getSpecificSubscription($received_data);
		
		if(empty($subscription_detail)){
			return json_encode(array('status' => 'FALSE', 'message' => 'No Subscription Detail found'));
		}

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

			$notification_data = [
				"title" => "WebPush Notification From Server", 
				"message" => "Hello From server! 👋", 
				"link" => "https://www.google.com", 
				"icon" => "https://freeiconshop.com/wp-content/uploads/edd/home-flat.png", 
				'campaign_id' => $campaign_id
			];

			$temp_notifications = [
				'subscription' => Subscription::create($subscription_data),
				'payload' => json_encode($notification_data),
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
		// print_r($this->Subscription_modal->saveFailToSentSubscriptions($not_sent_end_points_detail));
		// print_r($this->Subscription_modal->markSubscriptionsAsDelete($not_sent_end_points));
		// print_r($not_sent_end_points);

		
		$campain_detail = [
			'site_id' => $received_data['site_id'],
			'campaign_id' => $campaign_id,
			'subscribers' => count($subscription_detail),
			'sent_to' => count($sent_end_points),
			'fail_to_sent' => count($not_sent_end_points),
		];
		$campain = $this->Subscription_modal->createSubscriptionCampain($campain_detail);
		echo "campain: " . $campain;
	}

	public function updateSubscriptionCampainRecord(){
		$campain_data_to_update = json_decode(trim(file_get_contents('php://input')), true);
		$campain = $this->Subscription_modal->updateSubscriptionCampain($campain_data_to_update);
		echo json_encode(array('status' => $campain));
	}
}
