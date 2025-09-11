<?php
// reports/income_expenses.php

// Query to get product sales and cost data
$query = "SELECT 
            p.product_id,
            p.name as product_name,
            p.product_code,
            p.category_name,
            SUM(si.quantity) as total_sold,
            SUM(si.quantity * si.price) as total_revenue,
            SUM(si.quantity * p.cost_price) as total_cost,
            SUM((si.quantity * si.price) - (si.quantity * p.cost_price)) as gross_profit,
            CASE 
                WHEN SUM(si.quantity * si.price) > 0 
                THEN (SUM((si.quantity * si.price) - (si.quantity * p.cost_price)) / SUM(si.quantity * si.price)) * 100 
                ELSE 0 
            END as profit_margin
          FROM sale_items si
          JOIN products p ON si.product_id = p.product_id
          JOIN sales s ON si.sale_id = s.id
          WHERE s.date BETWEEN ? AND ?
          GROUP BY p.product_id
          ORDER BY total_revenue DESC";

$stmt = mysqli_prepare($conn, $query);
$end_date_full = $end_date . ' 23:59:59'; // Include full end day
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get totals for the period
$totals_query = "SELECT 
                  SUM(si.quantity * si.price) as total_revenue,
                  SUM(si.quantity * p.cost_price) as total_cost,
                  SUM((si.quantity * si.price) - (si.quantity * p.cost_price)) as total_profit
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.date BETWEEN ? AND ?";

$totals_stmt = mysqli_prepare($conn, $totals_query);
mysqli_stmt_bind_param($totals_stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($totals_stmt);
$totals_result = mysqli_stmt_get_result($totals_stmt);
$period_totals = mysqli_fetch_assoc($totals_result);

// Set report title
$report_title = 'Income and Expenses by Product';
?>

<div class="report-table">
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Units Sold</th>
                <th>Total Revenue</th>
                <th>Total Cost</th>
                <th>Gross Profit</th>
                <th>Profit Margin</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $has_data = false;
            while ($row = mysqli_fetch_assoc($result)): 
                $has_data = true;
                $profit_class = $row['gross_profit'] >= 0 ? 'text-success' : 'text-danger';
                $margin_class = $row['profit_margin'] >= 20 ? 'text-success' : 
                               ($row['profit_margin'] >= 10 ? 'text-warning' : 'text-danger');
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_code']) ?></td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td class="text-end"><?= number_format($row['total_sold']) ?></td>
                    <td class="text-end">$<?= number_format($row['total_revenue'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['total_cost'], 2) ?></td>
                    <td class="text-end <?= $profit_class ?>">$<?= number_format($row['gross_profit'], 2) ?></td>
                    <td class="text-end <?= $margin_class ?>"><?= number_format($row['profit_margin'], 1) ?>%</td>
                </tr>
            <?php endwhile; ?>
            
            <?php if (!$has_data): ?>
                <tr>
                    <td colspan="8" class="text-center">No sales data found for the selected period</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-group-divider">
            <tr class="fw-bold">
                <td colspan="3" class="text-end">Period Totals:</td>
                <td class="text-end">-</td>
                <td class="text-end">$<?= number_format($period_totals['total_revenue'] ?? 0, 2) ?></td>
                <td class="text-end">$<?= number_format($period_totals['total_cost'] ?? 0, 2) ?></td>
                <td class="text-end <?= ($period_totals['total_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                    $<?= number_format($period_totals['total_profit'] ?? 0, 2) ?>
                </td>
                <td class="text-end">
                    <?= ($period_totals['total_revenue'] ?? 0) > 0 ? 
                        number_format((($period_totals['total_profit'] ?? 0) / ($period_totals['total_revenue'] ?? 1)) * 100, 1) : 0 ?>%
                </td>
            </tr>
        </tfoot>
    </table>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Financial Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Revenue:</span>
                        <strong>$<?= number_format($period_totals['total_revenue'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Cost of Goods:</span>
                        <strong>$<?= number_format($period_totals['total_cost'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Gross Profit:</span>
                        <strong class="<?= ($period_totals['total_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format($period_totals['total_profit'] ?? 0, 2) ?>
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Profit Margin:</span>
                        <strong>
                            <?= ($period_totals['total_revenue'] ?? 0) > 0 ? 
                                number_format((($period_totals['total_profit'] ?? 0) / ($period_totals['total_revenue'] ?? 1)) * 100, 1) : 0 ?>%
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Performance Indicators</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate some performance metrics
                    $avg_profit_margin = ($period_totals['total_revenue'] ?? 0) > 0 ? 
                        (($period_totals['total_profit'] ?? 0) / ($period_totals['total_revenue'] ?? 1)) * 100 : 0;
                    
                    $margin_status = $avg_profit_margin >= 20 ? 'Good' : 
                                    ($avg_profit_margin >= 10 ? 'Average' : 'Poor');
                    
                    $margin_status_class = $avg_profit_margin >= 20 ? 'text-success' : 
                                         ($avg_profit_margin >= 10 ? 'text-warning' : 'text-danger');
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Average Profit Margin:</span>
                        <strong class="<?= $margin_status_class ?>"><?= number_format($avg_profit_margin, 1) ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Margin Status:</span>
                        <strong class="<?= $margin_status_class ?>"><?= $margin_status ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Return on Investment:</span>
                        <strong>
                            <?= ($period_totals['total_cost'] ?? 0) > 0 ? 
                                number_format((($period_totals['total_profit'] ?? 0) / ($period_totals['total_cost'] ?? 1)) * 100, 1) : 0 ?>%
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>