<?php

class Subscription_modal extends CI_Model{

    public function saveSubscription($postData, $public_key){
        $site_detail = $this->db->select("*")->from("sites")->where(['public_key' => $public_key])->get();
        $site_detail = $site_detail->result();
        $site_id = $site_detail[0]->id;
        
        $endpoint = $postData['endpoint'];
		$expirationTime = isset($postData['expirationTime']) ? $postData['expirationTime'] : "";
		$keys = $postData['keys'];
		$p256dh = $keys['p256dh'];
        $auth = $keys['auth'];
        
        $data_to_save = array (
            'site_id' => $site_id,
            'endpoint' => $endpoint,
            'expirationTime' => "",
            'p256dh' => $p256dh,
            'auth' => $auth
        );

        // $data_to_save_manually =  array ( 
        //     'site_id' => 4,
        //     'endpoint' => 'https://fcm.googleapis.com/fcm/send/eXYBnTRhv9o:APA91bFWInY0-YW6B_FEcKNd-jJdarAiJFNFBS88CDWEq_gwS6E7rT64YwP2KhRWQ4XQOWJPrkl7FZSRGZkYsQi6OVAImkxFiY0ioq_kP6ojwLyW9dOP_H3vWq4exQQpmpf2ayJVkdY5',
        //     'expirationTime' => '',
        //     'p256dh' => 'BEqhGPa5uqkNep2QoIEs3vyShsParHdEpMB5MnqGdzGVRTj_htxjBSCWykMjmgKHzRybT5b9HNVklKpfuacYX6Y',
        //     'auth' => 'WEnfgh5IXL_dpcIv2_ioJQ'
        // );

        // echo "data_to_save: "; print_r($data_to_save); 
        // echo "<br> data_to_save_manually: " ; print_r($data_to_save_manually);
        // return $data_to_save;
        // $result =  $this->db->insert_string('subscriptions', $data_to_save);
        $result =  $this->db->insert('subscriptions', $data_to_save);
        // return $result;
        if($result){
            return true;
        }
        return false;

		// $last_id = $this->db->insert_id();
		// return $last_id;
    }

    public function allSubscription($site_id){
        $all_subscriptions = $this->db->select("*")->from("subscriptions")->where(['site_id' => $site_id, 'is_deleted' => 0])->order_by('id', 'DESC')->get();        
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

    public function getCampainIdAfterSavingCampain($data){
        $data_to_save = ['site_id' => $data['site_id'], 'campaign_title' => $data['campaign_title']];
        $result =  $this->db->insert('campaigns', $data_to_save);
        if($result){
            return $this->db->insert_id();
        }
        return false;
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

    public function createSubscriptionCampain($campain_data){
        $result =  $this->db->insert('subscription_campaign', $campain_data);
        if($result){
            return true;
        }
        return false;
    }

    public function updateSubscriptionCampain($campain_data){
        $status_to_update='';
        if($campain_data['event'] == 0){
            $status_to_update = 'delivered_to';
        }
        else if($campain_data['event'] == 1){
            $status_to_update = 'closed';
        }
        else if($campain_data['event'] == 2){
            $status_to_update = 'clicks';
        }
        $sql = "UPDATE subscription_campaign SET ".$status_to_update." = (SELECT SUM(".$status_to_update." + 1) FROM (SELECT * FROM subscription_campaign  WHERE campaign_id=".$campain_data['campaign_id'].") AS sc2) WHERE campaign_id=".$campain_data['campaign_id'].";";
        $query_result = $this->db->query($sql);
        return $query_result;
    }
}
?>