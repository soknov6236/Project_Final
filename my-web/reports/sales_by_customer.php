<?php
// reports/sales_by_customer.php

// Query to get sales by customer
$query = "SELECT 
            c.id,
            c.customer_name,
            c.email,
            c.phone,
            COUNT(s.id) as transaction_count,
            COALESCE(SUM(s.total), 0) as total_sales,
            COALESCE(SUM(s.discount), 0) as total_discount,
            COALESCE(AVG(s.total), 0) as avg_transaction
          FROM customers c
          LEFT JOIN sales s ON c.id = s.customer_id
          WHERE s.date BETWEEN ? AND ?
          GROUP BY c.id
          ORDER BY total_sales DESC";

$end_date_full = $end_date . ' 23:59:59';
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get totals
$totals_query = "SELECT 
                   COUNT(id) as total_transactions,
                   SUM(total) as total_sales,
                   SUM(discount) as total_discount,
                   AVG(total) as overall_avg
                FROM sales
                WHERE date BETWEEN ? AND ?";
$totals_stmt = mysqli_prepare($conn, $totals_query);
mysqli_stmt_bind_param($totals_stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($totals_stmt);
$totals_result = mysqli_stmt_get_result($totals_stmt);
$totals = mysqli_fetch_assoc($totals_result);
?>

<div class="report-table">
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>Customer</th>
                <th>Contact</th>
                <th class="text-end">Transactions</th>
                <th class="text-end">Total Sales</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Avg. Transaction</th>
                <th class="text-end">% of Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php 
                $percentage = $totals['total_sales'] > 0 ? 
                    ($row['total_sales'] / $totals['total_sales']) * 100 : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td>
                        <div><?= htmlspecialchars($row['email'] ?: 'N/A') ?></div>
                        <small><?= htmlspecialchars($row['phone'] ?: '') ?></small>
                    </td>
                    <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                    <td class="text-end">$<?= number_format($row['total_sales'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['total_discount'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['avg_transaction'], 2) ?></td>
                    <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-group-divider">
            <tr>
                <th colspan="2" class="text-end">Totals:</th>
                <th class="text-end"><?= number_format($totals['total_transactions'] ?? 0) ?></th>
                <th class="text-end">$<?= number_format($totals['total_sales'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_discount'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['overall_avg'] ?? 0, 2) ?></th>
                <th class="text-end">100%</th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Customer Segmentation -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Top 5 Customers by Revenue</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php 
                        mysqli_data_seek($result, 0);
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($result) && $count < 5): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($row['customer_name']) ?>
                                <span class="badge bg-success rounded-pill">
                                    $<?= number_format($row['total_sales'], 2) ?>
                                </span>
                            </li>
                        <?php 
                        $count++;
                        endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Top 5 Customers by Frequency</h6>
                </div>
                <div class="card-body">
                    <?php 
                    // Re-query for top customers by transactions
                    $freq_query = str_replace(
                        "ORDER BY total_sales DESC", 
                        "ORDER BY transaction_count DESC", 
                        $query
                    );
                    $f_stmt = mysqli_prepare($conn, $freq_query);
                    mysqli_stmt_bind_param($f_stmt, "ss", $start_date, $end_date_full);
                    mysqli_stmt_execute($f_stmt);
                    $f_result = mysqli_stmt_get_result($f_stmt);
                    ?>
                    <ul class="list-group">
                        <?php 
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($f_result) && $count < 5): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($row['customer_name']) ?>
                                <span class="badge bg-success rounded-pill">
                                    <?= number_format($row['transaction_count']) ?> purchases
                                </span>
                            </li>
                        <?php 
                        $count++;
                        endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New vs Returning Customers -->
    <div class="card mt-4 border-warning">
        <div class="card-header bg-warning">
            <h6 class="mb-0">Customer Acquisition</h6>
        </div>
        <div class="card-body">
            <?php
            // Get new vs returning customers
            $acq_query = "SELECT 
                            SUM(CASE WHEN first_purchase.first_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_customers,
                            COUNT(DISTINCT s.customer_id) as total_customers
                          FROM sales s
                          JOIN (
                            SELECT customer_id, MIN(date) as first_date 
                            FROM sales 
                            GROUP BY customer_id
                          ) first_purchase ON s.customer_id = first_purchase.customer_id
                          WHERE s.date BETWEEN ? AND ?";
            $acq_stmt = mysqli_prepare($conn, $acq_query);
            mysqli_stmt_bind_param($acq_stmt, "ssss", $start_date, $end_date_full, $start_date, $end_date_full);
            mysqli_stmt_execute($acq_stmt);
            $acq_result = mysqli_stmt_get_result($acq_stmt);
            $acq = mysqli_fetch_assoc($acq_result);
            
            $returning_customers = $acq['total_customers'] - $acq['new_customers'];
            $new_percent = $acq['total_customers'] > 0 ? ($acq['new_customers'] / $acq['total_customers']) * 100 : 0;
            $returning_percent = $acq['total_customers'] > 0 ? ($returning_customers / $acq['total_customers']) * 100 : 0;
            ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Customers:</span>
                        <strong><?= number_format($acq['new_customers']) ?> (<?= number_format($new_percent, 1) ?>%)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Returning Customers:</span>
                        <strong><?= number_format($returning_customers) ?> (<?= number_format($returning_percent, 1) ?>%)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Active Customers:</span>
                        <strong><?= number_format($acq['total_customers']) ?></strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?= $new_percent ?>%;" 
                             aria-valuenow="<?= $new_percent ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            New: <?= number_format($new_percent, 1) ?>%
                        </div>
                        <div class="progress-bar bg-info" role="progressbar" 
                             style="width: <?= $returning_percent ?>%;" 
                             aria-valuenow="<?= $returning_percent ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            Returning: <?= number_format($returning_percent, 1) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  
</div>