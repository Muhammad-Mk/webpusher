<?php $this->load->view('common_files/header.php', ['header' => 'WebPusher: Subscriptions']); ?>

    <div class="row" style="padding-top: 3pc !important;">
        <div class="col-8">
            <h1>All Subscriptions</h1>
        </div>
        <div class="col-4 text-right">
            <button class="btn btn-primary" onclick="send_push_notification('<?= $site_id ?>')">Send To All</button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <table id="subscription_table" class="table table-striped table-bordered" style="width:100%">
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
                    <?php foreach($subscriptions as $subscription) { ?>
                    <tr>
                        <td><?= $subscription->id ?></td>
                        <td><?= substr($subscription->endpoint, 0, 30) . " ....." ?></td>
                        <td><?= substr($subscription->p256dh, 0, 20) . " ....." ?></td>
                        <td><?= $subscription->auth ?></td>
                        <td><button class="btn btn-sm btn-success" onclick="send_push_notification('<?= $subscription->site_id ?>', '<?= $subscription->id ?>')">Send Notification</button></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
        
<?php $this->load->view('common_files/footer.php'); ?>

<script>
    $(document).ready( function () {
        $('#subscription_table').DataTable();
    });

    function send_push_notification(site_id, subscription_id=''){
        console.log("site_id: ", site_id)
        console.log("subscription_id: ", subscription_id)
        if(subscription_id != ''){
            var data_to_send = {site_id: site_id, subscription_id: subscription_id, campaign_title: 'Practice campaign'};
        }
        else{
            var data_to_send = {site_id: site_id, campaign_title: 'Practice campaign'};
        }
        $.ajax({
            type: "post",
            url: "<?= base_url() . 'api/send-subscription' ?>",
            data: data_to_send,
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
