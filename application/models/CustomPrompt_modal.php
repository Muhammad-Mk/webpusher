<?php

class CustomPrompt_modal extends CI_Model{

    public function saveCustomPrompt($post_data){
        $data_to_save = [
            'site_id' => $post_data['site_id'],
            'prompt_title' => $post_data['prompt_title'],
            'prompt_detail' => $post_data['prompt_detail'],
            'prompt_url' => $post_data['prompt_url'],
            'prompt_delay' => $post_data['prompt_delay'],
            'prompt_enable' => $post_data['prompt_enable'],
            'prompt_disable' => $post_data['prompt_disable'],
            'prompt_icon' => $post_data['prompt_icon'],
            'prompt_native' => isset($post_data['prompt_native']) ? $post_data['prompt_native'] : 0,
        ];
        $result =  $this->db->insert('custom_prompts', $data_to_save);
        if($result){
            return $this->db->insert_id();
        }
        return false;
    }

    public function allCustomPrompts(){
        $all_sites = $this->db->select("*")->from("custom_prompts")->get();        
        return $all_sites->result();
    }

    public function getSpecificCustomPromptDetail($site_id){
        $custom_prompt_detail = $this->db->select("*")->from("custom_prompts")->where('site_id', $site_id)->get();        
        return $custom_prompt_detail->result();
    }

    public function deleteCustomPrompt($site_id){
        $delete_custom_prompt = $this->db->where('site_id', $site_id)->from('custom_prompts')->delete();
        return $delete_custom_prompt;
    }

    public function getCustomPromptDetailAgainstSiteKey($public_key){
        $custom_prompt_detail = $this->db->select("custom_prompts.*")->from("sites")
                                    ->join('custom_prompts', 'sites.id = custom_prompts.site_id')
                                    ->where('sites.public_key', $public_key)->get();        
        return $custom_prompt_detail->result();
    }
    
    public function checkSiteCustomPromptExist($site_id){
        $query = $this->db->where(['site_id' => $site_id])->from('custom_prompts')->get();
        if ($query->num_rows() > 0){
            return true;
        }
        else{
            return false;
        }
    }
}
?>