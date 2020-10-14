<?php $this->load->view('common_files/header.php', ['header' => 'WebPusher: Add Site']); ?>

    <h1>Add New Sites</h1>
    <div class="row">
        <div class="col-md-12">
            <form id="site_add" method="post" action="<?= base_url() . 'sites/save' ?>" >
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />
                <div class="row">
                    <div class="col-md-12 mt-3">
                        <input type="text" class="form-control" name="site_title" id="site_title" placeholder="site title" required />
                    </div>
                    <div class="col-md-12 mt-3">
                        <input type="url" class="form-control" name="site_url" id="site_url" placeholder="https://www.google.com" required />
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
    $("#site_add").submit(function(e) {
        e.preventDefault();
        $("#result_response").hide();

        var form = $(this);
        var url = form.attr('action');
        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: 'json',
            success: function(result){
                if(result.status === true){
                    $("#result_response").html(result.resulted_script).show();
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