<?php
// reports/sales_by_product.php

// Query to get sales by product
$query = "SELECT 
            p.id, 
            p.product_name, 
            p.product_code,
            COALESCE(SUM(si.quantity), 0) as total_quantity,
            COALESCE(SUM(si.total_price), 0) as total_sales,
            COALESCE(SUM(si.discount), 0) as total_discount,
            COALESCE(AVG(si.unit_price), 0) as avg_price
          FROM products p
          LEFT JOIN sale_items si ON p.id = si.product_id
          LEFT JOIN sales s ON si.sale_id = s.id
          WHERE s.date BETWEEN ? AND ?
          GROUP BY p.id
          ORDER BY total_sales DESC";

$end_date_full = $end_date . ' 23:59:59';
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get totals
$totals_query = "SELECT 
                   SUM(si.quantity) as total_quantity,
                   SUM(si.total_price) as total_sales,
                   SUM(si.discount) as total_discount
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE s.date BETWEEN ? AND ?";
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
                <th>Product</th>
                <th>Code</th>
                <th class="text-end">Quantity Sold</th>
                <th class="text-end">Total Sales</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Avg. Price</th>
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
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['product_code']) ?></td>
                    <td class="text-end"><?= number_format($row['total_quantity']) ?></td>
                    <td class="text-end">$<?= number_format($row['total_sales'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['total_discount'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['avg_price'], 2) ?></td>
                    <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-group-divider">
            <tr>
                <th colspan="2" class="text-end">Totals:</th>
                <th class="text-end"><?= number_format($totals['total_quantity'] ?? 0) ?></th>
                <th class="text-end">$<?= number_format($totals['total_sales'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_discount'] ?? 0, 2) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Top Performing Products -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Top 5 Products by Revenue</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php 
                        mysqli_data_seek($result, 0);
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($result) && $count < 5): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($row['product_name']) ?>
                                <span class="badge bg-primary rounded-pill">
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
                    <h6 class="mb-0">Top 5 Products by Quantity</h6>
                </div>
                <div class="card-body">
                    <?php 
                    // Re-query for top products by quantity
                    $quantity_query = str_replace(
                        "ORDER BY total_sales DESC", 
                        "ORDER BY total_quantity DESC", 
                        $query
                    );
                    $q_stmt = mysqli_prepare($conn, $quantity_query);
                    mysqli_stmt_bind_param($q_stmt, "ss", $start_date, $end_date_full);
                    mysqli_stmt_execute($q_stmt);
                    $q_result = mysqli_stmt_get_result($q_stmt);
                    ?>
                    <ul class="list-group">
                        <?php 
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($q_result) && $count < 5): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($row['product_name']) ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?= number_format($row['total_quantity']) ?>
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
</div>