<?php
// reports/sales_summary.php

// Query to get total sales, transactions, and averages
$query = "SELECT 
            COUNT(id) as total_transactions,
            SUM(total) as total_sales,
            AVG(total) as average_sale,
            SUM(tax) as total_tax,
            SUM(discount) as total_discount
          FROM sales
          WHERE date BETWEEN ? AND ?";

$end_date_full = $end_date . ' 23:59:59';
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt);
$summary_result = mysqli_stmt_get_result($stmt);
$summary = mysqli_fetch_assoc($summary_result);

// Query for payment method breakdown
$payment_query = "SELECT 
                    payment_method,
                    COUNT(id) as transaction_count,
                    SUM(total) as total_amount
                  FROM sales
                  WHERE date BETWEEN ? AND ?
                  GROUP BY payment_method";
$stmt_payment = mysqli_prepare($conn, $payment_query);
mysqli_stmt_bind_param($stmt_payment, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt_payment);
$payment_result = mysqli_stmt_get_result($stmt_payment);

// Query for payment status breakdown
$status_query = "SELECT 
                    payment_status,
                    COUNT(id) as transaction_count,
                    SUM(total) as total_amount
                 FROM sales
                 WHERE date BETWEEN ? AND ?
                 GROUP BY payment_status";
$stmt_status = mysqli_prepare($conn, $status_query);
mysqli_stmt_bind_param($stmt_status, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt_status);
$status_result = mysqli_stmt_get_result($stmt_status);

// Query for daily sales trend
$daily_query = "SELECT 
                  DATE(date) as sale_date,
                  COUNT(id) as transaction_count,
                  SUM(total) as total_sales
                FROM sales
                WHERE date BETWEEN ? AND ?
                GROUP BY DATE(date)
                ORDER BY DATE(date)";
$stmt_daily = mysqli_prepare($conn, $daily_query);
mysqli_stmt_bind_param($stmt_daily, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt_daily);
$daily_result = mysqli_stmt_get_result($stmt_daily);

// Calculate net sales
$net_sales = $summary['total_sales'] - $summary['total_discount'];
?>

<div class="report-table">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-2">Total Sales</h5>
                            <h3 class="mb-0">$<?= number_format($summary['total_sales'], 2) ?></h3>
                        </div>
                        <div class="bg-primary text-white p-3 rounded">
                            <i class="ti ti-shopping-cart fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success"><i class="ti ti-arrow-up"></i> Net: $<?= number_format($net_sales, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-2">Transactions</h5>
                            <h3 class="mb-0"><?= number_format($summary['total_transactions']) ?></h3>
                        </div>
                        <div class="bg-success text-white p-3 rounded">
                            <i class="ti ti-receipt fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-muted">Avg: $<?= number_format($summary['average_sale'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-2">Discounts</h5>
                            <h3 class="mb-0">$<?= number_format($summary['total_discount'], 2) ?></h3>
                        </div>
                        <div class="bg-info text-white p-3 rounded">
                            <i class="ti ti-discount fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-muted"><?= $summary['total_transactions'] > 0 ? number_format(($summary['total_discount'] / $summary['total_sales']) * 100, 2) : '0' ?>% of sales</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-2">Tax Collected</h5>
                            <h3 class="mb-0">$<?= number_format($summary['total_tax'], 2) ?></h3>
                        </div>
                        <div class="bg-warning text-white p-3 rounded">
                            <i class="ti ti-report-money fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-muted">Sales Tax</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Payment Method Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($payment_result)): ?>
                                    <?php 
                                    $percentage = $summary['total_sales'] > 0 ? 
                                        ($row['total_amount'] / $summary['total_sales']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?= ucfirst($row['payment_method']) ?></td>
                                        <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                                        <td class="text-end">$<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Status Breakdown -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Payment Status Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($status_result)): ?>
                                    <?php 
                                    $percentage = $summary['total_sales'] > 0 ? 
                                        ($row['total_amount'] / $summary['total_sales']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge 
                                                <?= $row['payment_status'] == 'paid' ? 'bg-success' : 
                                                   ($row['payment_status'] == 'pending' ? 'bg-warning' : 
                                                   ($row['payment_status'] == 'partial' ? 'bg-info' : 'bg-danger')) ?>">
                                                <?= ucfirst($row['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                                        <td class="text-end">$<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Sales Trend -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daily Sales Trend</h5>
            <small><?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Total Sales</th>
                            <th class="text-end">Avg. Sale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($daily_result)): ?>
                            <tr>
                                <td><?= date('D, M d, Y', strtotime($row['sale_date'])) ?></td>
                                <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                                <td class="text-end">$<?= number_format($row['total_sales'], 2) ?></td>
                                <td class="text-end">$<?= $row['transaction_count'] > 0 ? number_format($row['total_sales'] / $row['transaction_count'], 2) : '0.00' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr>
                            <th>Period Totals:</th>
                            <th class="text-end"><?= number_format($summary['total_transactions']) ?></th>
                            <th class="text-end">$<?= number_format($summary['total_sales'], 2) ?></th>
                            <th class="text-end">$<?= number_format($summary['average_sale'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Sales Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Gross Sales:</span>
                        <strong>$<?= number_format($summary['total_sales'], 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Discounts:</span>
                        <strong class="text-danger">- $<?= number_format($summary['total_discount'], 2) ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fw-bold">
                        <span>Net Sales:</span>
                        <strong>$<?= number_format($net_sales, 2) ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax Collected:</span>
                        <strong>$<?= number_format($summary['total_tax'], 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Transactions:</span>
                        <strong><?= number_format($summary['total_transactions']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Average Sale:</span>
                        <strong>$<?= number_format($summary['average_sale'], 2) ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Period:</span>
                        <strong><?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Days in Period:</span>
                        <strong>
                            <?php 
                            $start = new DateTime($start_date);
                            $end = new DateTime($end_date);
                            $days = $start->diff($end)->days + 1;
                            echo $days;
                            ?>
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Avg. Daily Sales:</span>
                        <strong>$<?= number_format($net_sales / $days, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>