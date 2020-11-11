<?php $this->load->view('common_files/header.php', ['header' => 'WebPusher: Custom Prompt']); ?>

    <h1>Custom Prompt</h1>
    <div class="row">
        <div class="col-md-12">

            <form id="custom_prompt_add" method="POST" action="<?= base_url() . 'custom-prompt/save' ?>" enctype="multipart/form-data">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />
                <?php if(count($custom_prompts_detail)){ ?>
                    <input type="hidden" name="id" value="<?= $custom_prompts_detail[0]->id ?>" />
                <?php } ?>
                <input type="hidden" name="site_id" value="<?= $site_id ?>" />
                <div class="row">
                    <div class="col-md-12 mt-3">
                        <label>Prompt Title</label>
                        <input type="text" class="col-8 form-control" name="prompt_title" id="prompt_title" placeholder="prompt title" required value="<?php if(isset($custom_prompts_detail[0]->prompt_title)) { echo $custom_prompts_detail[0]->prompt_title; } ?>"/>
                    </div>
                    <div class="col-md-12 mt-3">
                        <label>Prompt Detail</label>
                        <input type="text" class="col-8 form-control" name="prompt_detail" id="prompt_detail" required value="<?php if(isset($custom_prompts_detail[0]->prompt_detail)) { echo $custom_prompts_detail[0]->prompt_detail; } ?>"/>
                    </div>
                    <div class="col-md-12 mt-3">
                        <label>Prompt Link URL</label>
                        <input type="url" class="col-8 form-control" name="prompt_url" id="prompt_url" required value="<?php if(isset($custom_prompts_detail[0]->prompt_url)) { echo $custom_prompts_detail[0]->prompt_url; } ?>"/>
                    </div>
                    <div class="col-md-12 mt-3">
                        <label>Prompt Delay in Days</label>
                        <input type="number" class="col-8 form-control" name="prompt_delay" id="prompt_delay" required value="<?php if(isset($custom_prompts_detail[0]->prompt_delay)) { echo $custom_prompts_detail[0]->prompt_delay; } ?>"/>
                    </div>
                    <div class="col-md-12 mt-3">
                        <label>Prompt Icon</label>
                        <input type="file" class="col-8 form-control" name="prompt_icon" required />
                    </div>
                    <div class="col-md-12 mt-3">
                        <label>Prompt Buttons Text</label>
                        <div class="row">
                            <div class="col-2">
                                <input type="radio" name="prompt_native" value="1" value="<?php if(isset($custom_prompts_detail[0]->prompt_native) && $custom_prompts_detail[0]->prompt_native == 1) { echo 'checked'; } ?>"/> Native Prompt
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="prompt_enable" id="prompt_enable" placeholder="YES" required value="<?php if(isset($custom_prompts_detail[0]->prompt_enable)) { echo $custom_prompts_detail[0]->prompt_enable; } ?>"/>
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="prompt_disable" id="prompt_disable" placeholder="NO" required value="<?php if(isset($custom_prompts_detail[0]->prompt_disable)) { echo $custom_prompts_detail[0]->prompt_disable; } ?>"/>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <input type="submit" class="btn btn-success" name="site_submit" id="site_submit" value="Save" />
                    </div>
                </div>
            </form>

            <div class="row" id="result_response" style="display:none;"></div>
        </div>
    </div>

<?php $this->load->view('common_files/footer.php'); ?>

<script>
    $("#custom_prompt_add").submit(function(e) {
        e.preventDefault();
        $("#result_response").hide();

        // var form = $(this);
        // var url = form.attr('action');
        $.ajax({
            type: $(this).attr("method"),
            url: $(this).attr("action"),
            dataType: 'json',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(result){
                if(result.status === true){
                    $("#result_response").html(result.message).show();
                }
                else{
                    alert(result.message);
                }
            },
            error: function (error_result){
                console.log("error_result: ", error_result)
            }
        });
    });
</script>