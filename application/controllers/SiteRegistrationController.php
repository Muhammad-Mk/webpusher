<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Minishlink\WebPush\VAPID;


class SiteRegistrationController extends CI_Controller {

	public function __construct(){
        parent::__construct();
        $this->load->model('SiteRegistration_modal');
        $this->load->model('CustomPrompt_modal');
    }

    public function getAllSites(){
        $sites = $this->SiteRegistration_modal->allSites();
        $this->load->view('sites/index', ['sites' => $sites]);
    }

    public function getCreateSite(){
        $this->load->view('sites/add');
    }

    public function getEditSite($site_id){
        $site_detail = $this->SiteRegistration_modal->getSpecificSiteDetail($site_id);
        $this->load->view('sites/add', ['site_detail' => $site_detail]);
    }

    public function postSaveSite(){
        $post_data = $this->security->xss_clean($this->input->post());
        if($this->SiteRegistration_modal->site_exist('site_title', $post_data['site_title'])){
            echo json_encode(array('status' => FALSE, 'message' => 'site already registered with that name.'));
            return;
        }
        if($this->SiteRegistration_modal->site_exist('site_url', $post_data['site_url'])){
            echo json_encode(array('status' => FALSE, 'message' => 'site already registered with that url.'));
            return;
        }

        $vapid_keys = VAPID::createVapidKeys();
        $post_data['public_key'] = $vapid_keys['publicKey'];
        $post_data['private_key'] = $vapid_keys['privateKey'];
        $result_response = $this->SiteRegistration_modal->saveSite($post_data);
		if($result_response){

            // $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'];
            $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $data_to_create_site_custom_prompt = Array ( 
                'site_id' => $result_response['site_id'],
                'prompt_title' => 'title',
                'prompt_detail' => 'custom prompt title detail repeat custom prompt title detail repeat',
                'prompt_url' => $url,
                'prompt_delay' => 3,
                'prompt_enable' => 'YES',
                'prompt_disable' => 'NO',
                'prompt_icon' => 'https://freeiconshop.com/wp-content/uploads/edd/home-flat.png'
            );
            $this->CustomPrompt_modal->saveCustomPrompt($data_to_create_site_custom_prompt);


            $webpushr_url = base_url() . "web-push.js";
            $resulted_script = "<textarea class='form-control' rows='7' readonly>
            <script>
                (function(p,u,s,h,x){p.webpushr=p.webpushr||function(){(p.webpushr.q=p.webpushr.q||[]).push(arguments)};h=u.getElementsByTagName('head')[0];x=u.createElement('script');x.async=1;x.src=s;h.appendChild(x);})(window,document,'". $webpushr_url ."');
                webpushr('init', '". $result_response['public_key'] ."');
            </script>
            </textarea>";
			echo json_encode(array('status' => TRUE, 'resulted_script' => $resulted_script));
		}
		else{
			echo json_encode(array('status' => FALSE, 'message' => 'Something went wrong, unable to save site'));
		}
    }

    public function postDeleteSite(){
        $post_data = $this->security->xss_clean($this->input->post());
        if($this->SiteRegistration_modal->deleteSite($post_data['site_id'])){
            echo json_encode(array('status' => TRUE, 'message' => 'site deleted successfully.'));
            return;
        }
        echo json_encode(array('status' => FALSE, 'message' => 'Someting went wrong, unable to delete site.'));
        return;
    }
}