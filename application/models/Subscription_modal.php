<?php

class Subscription_modal extends CI_Model{

    public function saveSubscription($postData, $public_key){
        $site_detail = $this->db->select("*")->from("sites")->where(['public_key' => $public_key])->get();
        $site_detail = $site_detail->result();
        $site_id = $site_detail[0]->id;

        $endpoint = $postData['endpoint'];
		$expirationTime = isset($postData['expirationTime']) ? $postData['expirationTime'] : '';
		$keys = $postData['keys'];
		$p256dh = $keys['p256dh'];
        $auth = $keys['auth'];
        
        $data_to_save = [
            'site_id' => $site_id,
            'endpoint' => $endpoint,
            'expirationTime' => $expirationTime,
            'p256dh' => $p256dh,
            'auth' => $auth
        ];

        $result =  $this->db->insert('subscriptions', $data_to_save);
        if($result){
            return true;
        }
        return false;

		// $last_id = $this->db->insert_id();
		// return $last_id;
    }

    public function allSubscription($site_id){
        $all_subscriptions = $this->db->select("*")->from("subscriptions")->where(['site_id' => $site_id, 'is_deleted' => 0])->get();        
        return $all_subscriptions->result();
    }

    public function getSpecificSubscription($data){
        $condition = ['subscriptions.site_id' => $data['site_id'], 's.status' => 1];
        if(isset($data['subscription_id'])){
            $condition['subscriptions.id'] = $data['subscription_id'];
        }
        
        $subscription_detail = $this->db->select("subscriptions.id, subscriptions.site_id, subscriptions.endpoint, subscriptions.expirationTime, subscriptions.p256dh, subscriptions.auth, s.site_title, s.site_url, s.public_key, s.private_key")->from("subscriptions")
                                    ->join('sites as s', 's.id = subscriptions.site_id')
                                    ->where($condition)->get();        
        return $subscription_detail->result();
    }

    public function saveFailToSentSubscriptions($not_sent_subsriptions_records){
        $result =  $this->db->insert_batch('subscriptions_sent_fail', $not_sent_subsriptions_records);
        if($result){
            return true;
        }
        return false;
    }

    public function markSubscriptionsAsDelete($not_sent_subsriptions_records){
        $query = $this->db->where_in('endpoint',$not_sent_subsriptions_records)->update('subscriptions', ['is_deleted' => 1]);
        return $query;
    }
}
?>