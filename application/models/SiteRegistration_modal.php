<?php

class SiteRegistration_modal extends CI_Model{

    function site_exist($key, $value) {
        $query = $this->db->where([$key => $value])->from('sites')->get();
        if ($query->num_rows() > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public function saveSite($post_data){
        $data_to_save = [
            'site_title' => $post_data['site_title'],
            'site_url' => $post_data['site_url'],
            'public_key' => $post_data['public_key'],
            'private_key' => $post_data['private_key'],
        ];
        $result =  $this->db->insert('sites', $data_to_save);
        if($result){
            return ['public_key' => $post_data['public_key'], 'site_id' => $this->db->insert_id()];
        }
        return false;
    }

    public function allSites(){
        $all_sites = $this->db->select("*")->from("sites")->get();        
        return $all_sites->result();
    }

    public function getSpecificSiteDetail($site_id){
        $site_detail = $this->db->select("*")->from("sites")->where('id', $subscription_id)->get();        
        return $site_detail->result();
    }

    public function deleteSite($site_id){
        $delete_site = $this->db->where('id', $site_id)->from('sites')->delete();
        $delete_custom_prompt = $this->db->where('site_id', $site_id)->from('custom_prompts')->delete();
        return $delete_site;
    }
}

?>