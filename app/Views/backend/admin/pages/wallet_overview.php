<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('wallet_overview', 'Provider Wallet Overview') ?>
                <span class="breadcrumb-item p-3 pt-2 text-primary">
                    <i data-content="<?= labels('wallet_overview_info', 'View all provider wallet balances, commission deductions, top-ups, and withdrawals at a glance.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                </span>
            </h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/partners') ?>"><i class="fas fa-handshake text-warning"></i> <?= labels('provider', 'Provider') ?></a></div>
                <div class="breadcrumb-item"><?= labels('wallet_overview', 'Provider Wallet Overview') ?></div>
            </div>
        </div>
        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-4 mb-3">
                                <div class="col-md-4 col-sm-2 mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="customSearch" placeholder="<?= labels('search_here', 'Search here!') ?>" aria-label="Search" aria-describedby="customSearchBtn">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="customSearchBtn">
                                                <i class="fa fa-search d-inline"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="wallet_overview_table"
                                    data-toggle="table"
                                    data-url="<?= base_url('admin/partners/wallet_overview_list'); ?>"
                                    data-side-pagination="server"
                                    data-pagination="true"
                                    data-page-list="[5, 10, 25, 50, 100]"
                                    data-search="false"
                                    data-show-refresh="true"
                                    data-sort-name="balance"
                                    data-sort-order="desc"
                                    data-query-params="walletOverviewQueryParams">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-visible="false" data-sortable="true">
                                                <?= labels('id', 'ID') ?>
                                            </th>
                                            <th data-field="provider_name" class="text-center" data-sortable="false">
                                                <?= labels('provider_name', 'Provider Name') ?>
                                            </th>
                                            <th data-field="email" class="text-center" data-sortable="false">
                                                <?= labels('email', 'Email') ?>
                                            </th>
                                            <th data-field="phone" class="text-center" data-sortable="false">
                                                <?= labels('phone', 'Phone') ?>
                                            </th>
                                            <th data-field="balance" class="text-center" data-sortable="true">
                                                <?= labels('wallet_balance', 'Wallet Balance') ?>
                                            </th>
                                            <th data-field="total_commission" class="text-center" data-sortable="false">
                                                <?= labels('total_commission', 'Total Commission') ?>
                                            </th>
                                            <th data-field="total_topups" class="text-center" data-sortable="false">
                                                <?= labels('total_topups', 'Total Top-ups') ?>
                                            </th>
                                            <th data-field="total_withdrawals" class="text-center" data-sortable="false">
                                                <?= labels('total_withdrawals', 'Total Withdrawals') ?>
                                            </th>
                                            <th data-field="transactions" class="text-center" data-sortable="false">
                                                <?= labels('transactions', 'Transactions') ?>
                                            </th>
                                            <th data-field="action" class="text-center" data-sortable="false">
                                                <?= labels('action', 'Action') ?>
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    function walletOverviewQueryParams(params) {
        var search = $('#customSearch').val();
        return {
            offset: params.offset,
            limit: params.limit,
            sort: params.sort,
            order: params.order,
            search: search
        };
    }

    $(document).ready(function() {
        $('#customSearchBtn').on('click', function() {
            $('#wallet_overview_table').bootstrapTable('refresh');
        });
        $('#customSearch').on('keypress', function(e) {
            if (e.which === 13) {
                $('#wallet_overview_table').bootstrapTable('refresh');
            }
        });
    });
</script>
