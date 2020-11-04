<?php $this->load->view('common_files/header.php',  ['header' => 'WebPusher: Site Detail']); ?>

    <h4><?= $site_detail->site_title . " (" . $site_detail->site_url . ")" ?></h4>
    <a href="<?= base_url() . 'service-worker.js' ?>" class="btn btn-primary float-right" download><i class="fa fa-download"></i></a>


    <textarea class='form-control' rows='7' readonly>
        <script>
            (function(p,u,s,h,x){p.webpushr=p.webpushr||function(){(p.webpushr.q=p.webpushr.q||[]).push(arguments)};h=u.getElementsByTagName('head')[0];x=u.createElement('script');x.async=1;x.src=s;h.appendChild(x);})(window,document, <?= '"' .$webpushr_url . '"' ?> );
            webpushr('init', <?= '"'. $site_detail->public_key . '"' ?>);
        </script>
    </textarea>

<?php $this->load->view('common_files/footer.php'); ?>

<script>
    $(document).ready( function () {
        $('#sites_table').DataTable();
    });

    function deleteSiteInformation(site_id){
        console.log("Site_id: ", site_id);
        $.ajax({
            type: "POST",
            url: "<?= base_url() ?>" + "sites/delete",
            data: {"site_id" : site_id},
            dataType: 'json',
            success: function(result){
                console.log("result: ", result);
                if(result.status == true){
                    window.location.reload();
                }
                else{
                    alert(result.message);
                }
            },
            error: function (error_result){
                console.log("error_result: ", error_result)
            }
        });
    }
</script>
