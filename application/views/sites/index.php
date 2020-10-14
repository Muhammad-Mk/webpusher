<?php $this->load->view('common_files/header.php',  ['header' => 'WebPusher: All Sites']); ?>

    <h1>All User Sites</h1>
    <a href="<?= base_url() . 'sites/create' ?>" class="btn btn-primary float-right">Add New</a>
    <div class="row">
        <div class="col-md-12">
            <table id="sites_table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>URL</th>
                        <th>Public Key</th>
                        <th>action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($sites as $site) { ?>
                    <tr>
                        <td><?= $site->id ?></td>
                        <td><?= $site->site_title ?></td>
                        <td><?= $site->site_url ?></td>
                        <td><?= substr($site->public_key, 0, 30) . "....." ?></td>
                        <td style="display: flex;">
                            <a href="<?= base_url() . "subscriptions/" . $site->id ?>" class="btn btn-sm btn-success m-1"><i class="fa fa-exclamation"></i></a>
                            <a href="<?= base_url() . "custom-prompt/id/" . $site->id ?>" class="btn btn-sm btn-primary m-1"><i class="fa fa-plus"></i></a>
                            <a href="<?= base_url() . "sites/edit/" . $site->id ?>" class="btn btn-sm btn-warning m-1"><i class="fa fa-edit"></i></a>
                            <button onclick="deleteSiteInformation('<?= $site->id ?>')" class="btn btn-sm btn-danger m-1"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
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
