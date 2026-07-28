<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo $page_title; ?>
                    <a href="<?php echo site_url('admin/coupon_form/add_coupon_form'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle"><i class="mdi mdi-plus"></i><?php echo get_phrase('add_new_coupon'); ?></a>
                </h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <?php
                $coupon_count = is_array($coupons) ? count($coupons) : 0;
                $active_coupon_count = 0;
                foreach ($coupons as $coupon_summary) {
                    if (!isset($coupon_summary['status']) || $coupon_summary['status'] === 'active') {
                        $active_coupon_count++;
                    }
                }
                ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-1 header-title"><?php echo get_phrase('coupons'); ?></h4>
                        <small class="text-muted"><?php echo $coupon_count; ?> total · <?php echo $active_coupon_count; ?> active</small>
                    </div>
                    <div class="d-flex align-items-center mt-2 mt-md-0" style="gap: 8px;">
                        <label class="mb-0 text-muted" for="coupon-status-filter">Status</label>
                        <select id="coupon-status-filter" class="form-control form-control-sm" style="min-width: 130px;">
                            <option value="">All coupons</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive-sm mt-4">
                    <table id="coupons-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('coupon_code'); ?></th>
                                <th><?php echo get_phrase('discount_percentage'); ?></th>
                                <th><?php echo get_phrase('start_date'); ?></th>
                                <th><?php echo get_phrase('validity_till'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted mb-2"><i class="mdi mdi-ticket-percent-outline mdi-36px"></i></div>
                                        <h5 class="mb-1">No coupons yet</h5>
                                        <p class="text-muted mb-3">Create your first coupon to make it available at checkout.</p>
                                        <a href="<?php echo site_url('admin/coupon_form/add_coupon_form'); ?>" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Add new coupon
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($coupons as $key => $coupon) : ?>
                                <tr>
                                    <td><?php echo $key + 1; ?></td>
                                    <td><strong><?php echo html_escape($coupon['code']); ?></strong></td>
                                    <td><?php echo $coupon['discount_percentage']; ?>%</td>
                                    <td><?php echo !empty($coupon['start_date']) ? date('D, d-M-Y', $coupon['start_date']) : '-'; ?></td>
                                    <td><?php echo date('D, d-M-Y', $coupon['expiry_date']); ?></td>
                                    <td><span class="badge badge-<?php echo (!isset($coupon['status']) || $coupon['status'] === 'active') ? 'success' : 'secondary'; ?>"><?php echo !empty($coupon['status']) ? ucfirst($coupon['status']) : 'Active'; ?></span></td>
                                    <td>
                                        <div class="dropright dropright">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?php echo site_url('admin/coupon_form/edit_coupon_form/' . $coupon['id']) ?>"><?php echo get_phrase('edit'); ?></a></li>
                                                <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('admin/coupons/delete/' . $coupon['id']); ?>');"><?php echo get_phrase('delete'); ?></a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<script>
    $(function () {
        var couponTable = $('#coupons-datatable').DataTable({
            pageLength: 15,
            order: [[4, 'desc']],
            responsive: true,
            language: {
                search: '',
                searchPlaceholder: 'Search coupon code...',
                lengthMenu: 'Show _MENU_ coupons',
                info: 'Showing _START_ to _END_ of _TOTAL_ coupons',
                emptyTable: 'No coupons found',
                zeroRecords: 'No matching coupons found',
                paginate: {
                    previous: '<i class="mdi mdi-chevron-left"></i>',
                    next: '<i class="mdi mdi-chevron-right"></i>'
                }
            },
            columnDefs: [{ orderable: false, targets: [0, 6] }]
        });

        $('#coupon-status-filter').on('change', function () {
            couponTable.column(5).search(this.value).draw();
        });
    });
</script>
