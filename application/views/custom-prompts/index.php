<?php include_once('common_files/header.php'); ?>

    <h1>All Custom Prompt Details</h1>
    <a href="<?= base_url() . 'custom-prompt/create' ?>" class="btn btn-primary">Add New</a>
    <div class="row">
        <div class="col-md-12">
            <table id="custom_prompt_table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>EndPoint</th>
                        <th>p256dh</th>
                        <th>auth</th>
                        <th>action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($custom_prompts as $subscription) { ?>
                    <tr>
                        <td><?= $subscription->id ?></td>
                        <td><?= substr($subscription->endpoint, 0, 30) . " ....." ?></td>
                        <td><?= substr($subscription->p256dh, 0, 20) . " ....." ?></td>
                        <td><?= $subscription->auth ?></td>
                        <td><button class="btn btn-sm btn-success" onclick="send_push_notification('<?= $subscription->id ?>')">Send Notification</button></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
<?php include_once('common_files/footer.php'); ?>

<script>
    $(document).ready( function () {
        $('#custom_prompt_table').DataTable();
    });

    function send_push_notification(subscription_id){
        console.log("subscription_id: ", subscription_id)
        $.ajax({
            type: "post",
            url: "<?= base_url() . 'api/send-subscription' ?>",
            data: {subscription_id: subscription_id},
            cache: false,
            success: function(result){
                console.log("result: ", result)
            },
            error: function(error_result){
                console.log("error_result: ", error_result)
            }
        });
    }
</script>
