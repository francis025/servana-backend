<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('wallet_transactions', 'Wallet Transactions') ?>
                <?php if (!empty($provider)): ?>
                    - <?= htmlspecialchars($provider['username'] ?? '') ?>
                    <small class="text-muted">(<?= labels('balance', 'Balance') ?>: <?= number_format((float)($provider['balance'] ?? 0), 2) ?>)</small>
                <?php endif; ?>
            </h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/partners') ?>"><i class="fas fa-handshake text-warning"></i> <?= labels('provider', 'Provider') ?></a></div>
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/partners/wallet_overview') ?>"><i class="fas fa-wallet text-success"></i> <?= labels('wallet_overview', 'Wallet Overview') ?></a></div>
                <div class="breadcrumb-item"><?= labels('wallet_transactions', 'Wallet Transactions') ?></div>
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
                                <div class="col-md-3 col-sm-2 mb-2">
                                    <select class="form-control" id="typeFilter">
                                        <option value=""><?= labels('all_types', 'All Types') ?></option>
                                        <option value="topup"><?= labels('topup', 'Top Up') ?></option>
                                        <option value="commission_deduction"><?= labels('commission_deduction', 'Commission Deduction') ?></option>
                                        <option value="commission_refund"><?= labels('commission_refund', 'Commission Refund') ?></option>
                                        <option value="withdrawal"><?= labels('withdrawal', 'Withdrawal') ?></option>
                                        <option value="admin_credit"><?= labels('admin_credit', 'Admin Credit') ?></option>
                                        <option value="admin_debit"><?= labels('admin_debit', 'Admin Debit') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="wallet_txn_table"
                                    data-toggle="table"
                                    data-url="<?= base_url('admin/partners/wallet_transactions_list'); ?>"
                                    data-side-pagination="server"
                                    data-pagination="true"
                                    data-page-list="[10, 25, 50, 100]"
                                    data-search="false"
                                    data-show-refresh="true"
                                    data-sort-name="id"
                                    data-sort-order="desc"
                                    data-query-params="walletTxnQueryParams">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable="true">
                                                <?= labels('id', 'ID') ?>
                                            </th>
                                            <th data-field="type" class="text-center" data-sortable="true">
                                                <?= labels('type', 'Type') ?>
                                            </th>
                                            <th data-field="amount" class="text-center" data-sortable="true">
                                                <?= labels('amount', 'Amount') ?>
                                            </th>
                                            <th data-field="balance_before" class="text-center" data-sortable="false">
                                                <?= labels('balance_before', 'Balance Before') ?>
                                            </th>
                                            <th data-field="balance_after" class="text-center" data-sortable="false">
                                                <?= labels('balance_after', 'Balance After') ?>
                                            </th>
                                            <th data-field="commission_percentage" class="text-center" data-sortable="false">
                                                <?= labels('commission_pct', 'Commission %') ?>
                                            </th>
                                            <th data-field="description" class="text-center" data-sortable="false">
                                                <?= labels('description', 'Description') ?>
                                            </th>
                                            <th data-field="order_id" class="text-center" data-sortable="false">
                                                <?= labels('order_id', 'Order ID') ?>
                                            </th>
                                            <th data-field="created_at" class="text-center" data-sortable="true">
                                                <?= labels('date', 'Date') ?>
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
    function walletTxnQueryParams(params) {
        return {
            offset: params.offset,
            limit: params.limit,
            sort: params.sort,
            order: params.order,
            search: $('#customSearch').val(),
            provider_id: '<?= $provider_id ?? '' ?>',
            type_filter: $('#typeFilter').val()
        };
    }

    $(document).ready(function() {
        $('#customSearchBtn').on('click', function() {
            $('#wallet_txn_table').bootstrapTable('refresh');
        });
        $('#customSearch').on('keypress', function(e) {
            if (e.which === 13) {
                $('#wallet_txn_table').bootstrapTable('refresh');
            }
        });
        $('#typeFilter').on('change', function() {
            $('#wallet_txn_table').bootstrapTable('refresh');
        });
    });
</script>
