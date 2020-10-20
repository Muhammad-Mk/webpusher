<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CustomPromptController extends CI_Controller {

	public function __construct(){
        parent::__construct();
        $this->load->model('CustomPrompt_modal');
    }

    public function getAllCustomPrompts(){
        $custom_prompts = $this->CustomPrompt_modal->allCustomPrompts();
        $this->load->view('custom-prompts/index', ['custom_prompts' => $custom_prompts]);
    }

    public function getSiteCustomPrompts($site_id){
        $custom_prompts_detail = $this->CustomPrompt_modal->getSpecificCustomPromptDetail($site_id);
        $data['custom_prompts_detail'] = $custom_prompts_detail;
        $data['site_id'] = $site_id;
        // print_r($data);return;
        $this->load->view('custom-prompts/add', $data);
    }

    public function postCustomPromptDetail(){
        $post_data = json_decode(trim(file_get_contents('php://input')), true);
        $custom_prompt_of_site = $this->CustomPrompt_modal->getCustomPromptDetailAgainstSiteKey($post_data['site_key']);
        // print_r($custom_prompt_of_site);return;
        if(!$custom_prompt_of_site){
            echo json_encode(array('status' => FALSE, 'message' => 'Custom prompt Detail bot found against that Key'));
            return;
        }

        $site_url = $custom_prompt_of_site[0]->site_url;
        // echo "site url: " . $site_url . ", server: " . $_SERVER['HTTP_REFERER'] . "<br>"; return;
        if($site_url != $_SERVER['HTTP_REFERER']){
            echo json_encode(array('status' => FALSE, 'message' => 'Script not relevant to that site. Please use appropirate script'));
            return;
        }
        $custom_prompt_css = "<style>@keyframes _WPShowPromptBoxDrop-in{   from{top:0}   to{top:15px}}@keyframes _WPShowPromptBoxSlide-in{   from{top:-300px}   to{top:0px}}@keyframes _WPSlideUpPromptBox{   from{bottom:-1000px}   to{bottom:0px}}webpushrPromptConatiner{-webkit-animation:_WPShowPromptBoxSlide-in 0.5s forwards;animation:_WPShowPromptBoxSlide-in 0.5s forwards}webpushrPromptConatiner.Drop-in{-webkit-animation:_WPShowPromptBoxDrop-in 1s forwards;animation:_WPShowPromptBoxDrop-in 1s forwards}webpushrPromptConatiner{position:fixed;z-index:1000201000000000000000;top:15px;left:calc(50% - 245px);font-family:Arial,Helvetica,sans-serif!important;display:block;}wppromptbox2{-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;position:relative;background:#fff;width:470px;padding:10px;padding-top:20px;display:block;box-shadow:0px 0px 0px -4px rgba(39,55,74,.25), 0 15px 40px 10px rgba(39,55,74,.25);font-size:16px;line-height:20px;letter-spacing:normal;text-transform:initial; box-sizing:content-box}wppromptbox2 prompticon2{width:22%;float:left;display:block;margin:0 auto;text-align:center}wppromptbox2 prompticon2 img{width:75px;height:75px;display:inline-block;}wppromptbox2 promptbody2{width:72%;float:left;text-align:left;font-size:16px;padding-left:10px;box-sizing: content-box;}wppromptbox2 promptbox2 promptbuttons2{position:relative;display:inline-block;clear:both;width:100%;font-size:10px;padding-top:10px;padding-bottom:10px;direction:ltr}wppromptbox2 promptbox2 promptbuttons2 promptbtnapprove2,wppromptbox2 promptbox2 promptbuttons2 promptbtndeny2{display:inline-block;margin-top:10px;float:right;margin-right:10px;min-width:107px}wppromptbox2 headline2{font-weight:700;font-size:17px;padding-bottom:7px}wppromptbox2 headline2,wppromptbox2 headline2-byline{display:block;position:relative;text-align:initial}prompttext2{font-weight:400 !important; display:block;text-align:initial}wppromptbox2 promptbtnapprove2,wppromptbox2 promptbtndeny2{cursor:pointer;transition:all 60ms ease-in-out;text-align:center;white-space:nowrap;color:#fff;border:0 none;border-radius:2px;padding:14px 20px; box-sizing: content-box;}wppromptbox2 img._bell{margin:0 auto;cursor:pointer}wppromptbox2 poweredby2{font-size:12px;font-family:Arial,Helvetica,sans-serif!important;padding:0 5px;text-align:left;display:inline-block;margin-left:5px;margin-top:20px;opacity:.3;font-weight:600}wppromptbox2 poweredby2 svg{width:16px;fill:#000;position:relative;top:2px}wppromptbox2 poweredby2 a,wppromptbox2 poweredby2 a:hover{color:#000!important;font-size:12px!important;margin-left:-2px}@media only screen and (max-device-width:720px) and (orientation:landscape){wppromptbox2{display:none}}@media (max-width:480px){webpushrPromptConatiner{left:0!important; }wppromptbox2{animation:_WPSlideUpPromptBox 1s forwards;border-radius:0!important;width:initial;position:fixed!important;bottom:-1000px;min-height:150px;padding-top:10px;width:100%;left:0;box-shadow:0 0 20px 3px rgba(0,0,0,.22)!important;box-sizing:border-box;}promptbox2{display:block;padding-top:10px}wppromptbox2 headline2,wppromptbox2 promptbox2 promptbuttons2,wppromptbox2 prompttext2{font-size:14px}wppromptbox2 prompticon2 img{width:65px;height:65px}wppromptbox2 poweredby2{font-size:10px;margin-top:20px}}._prompt_overlay{background:rgba(66,61,61,.83);position:absolute;top:0;left:0;right:0;bottom:0;z-index:100000000000000000000000000000000;scroll-behavior:unset}#_prompt_overlay{display:none}webpushrpromptconatiner *{font-family:Arial,Helvetica,sans-serif!important;font-size:16px!important}wppromptbox2 poweredby2 *{font-weight:400 !important; font-size:14px !important;}</style>";
        $custom_prompt_div = '<div id="_prompt_overlay">
                                <webpushrPromptConatiner class="Slide-in">
                                    <wppromptbox2  style="background-color:#ffffff">
                                        <promptbox2>
                                            <prompticon2 class="_bell">
                                                <img style="visibility:visible !important;" alt="Logo" onload="document.getElementById('."'_prompt_overlay'".').style.display = '."'block'".';" src="'. $custom_prompt_of_site[0]->prompt_icon .'">
                                            </prompticon2>
                                        <promptbody2>
                                            <headline2 class="headline" style="color:#333333">'. $custom_prompt_of_site[0]->prompt_title .'</headline2>
                                            <prompttext2 class="prompt-text" style="color:#333333">'. $custom_prompt_of_site[0]->prompt_detail .'</prompttext2>
                                        </promptbody2>
                                        <promptbuttons2>
                                            <promptbtnapprove2 class="_promptApprove button-approve" onclick="webpushr('. "'webpushrPromptAction'" .', '."'Approve'".');" style="background-color: #ffd500; color: #000000;">'. $custom_prompt_of_site[0]->prompt_enable .'</promptbtnapprove2>
                                            <promptbtndeny2  class="_promptDeny button-deny"  onclick="webpushr('. "'webpushrPromptAction'" .', '."'Dismiss'".');" style="background-color: #ffffff; color: #a8a8a8;">'. $custom_prompt_of_site[0]->prompt_disable .'</promptbtndeny2>
                                        </promptbuttons2>
                                        </promptbox2>
                                    </wppromptbox2>
                                </webpushrPromptConatiner>
                            </div>';
        $custom_prompt_to_return = $custom_prompt_css . $custom_prompt_div;

        echo json_encode(array('status' => TRUE, 'custom_prompt' => $custom_prompt_to_return, 'reshow_prompt' => $custom_prompt_of_site[0]->prompt_delay, 'show_native_prompt' => $custom_prompt_of_site[0]->prompt_native));
    }

    public function postSaveCustomPrompts(){
        $post_data = $this->security->xss_clean($this->input->post());
        if($this->CustomPrompt_modal->checkSiteCustomPromptExist($post_data['site_id'])){
            echo json_encode(array('status' => FALSE, 'message' => 'Custom prompt already saved against that site'));
            return;
        }
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["prompt_icon"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "ico" ) {
            echo json_encode(array('status' => TRUE, 'message' => "Sorry, only JPG, JPEG, PNG & ICO files are allowed."));
            return;
        }
        if (!move_uploaded_file($_FILES["prompt_icon"]["tmp_name"], $target_file)) {
            echo json_encode(array('status' => TRUE, 'message' => "Sorry, there was an error uploading your file."));
            return;
        } 
        $file_name = base_url() . $target_dir . $_FILES["prompt_icon"]["name"];
        $post_data['prompt_icon'] = $file_name;

        print_r($post_data); return;
        $result_response = $this->CustomPrompt_modal->saveCustomPrompt($post_data);
		if($result_response){
			echo json_encode(array('status' => TRUE, 'message' => 'Custom prompt saved successfully'));
		}
		else{
			echo json_encode(array('status' => FALSE, 'message' => 'Something went wrong, unable to save custom prompt'));
		}
    }
}