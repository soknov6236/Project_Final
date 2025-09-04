<?php
// reports/sales_by_payment.php

// Query to get sales by payment method
$query = "SELECT 
            payment_method,
            COUNT(id) as transaction_count,
            SUM(total) as total_sales,
            SUM(tax) as total_tax,
            SUM(discount) as total_discount,
            AVG(total) as avg_transaction
          FROM sales
          WHERE date BETWEEN ? AND ?
          GROUP BY payment_method
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
                   SUM(tax) as total_tax,
                   SUM(discount) as total_discount
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
                <th>Payment Method</th>
                <th class="text-end">Transactions</th>
                <th class="text-end">Total Sales</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Avg. Transaction</th>
                <th class="text-end">% of Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php 
                $percentage = $totals['total_sales'] > 0 ? 
                    ($row['total_sales'] / $totals['total_sales']) * 100 : 0;
                ?>
                <tr>
                    <td><?= ucfirst($row['payment_method']) ?></td>
                    <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                    <td class="text-end">$<?= number_format($row['total_sales'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['total_tax'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['total_discount'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['avg_transaction'], 2) ?></td>
                    <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-group-divider">
            <tr>
                <th class="text-end">Totals:</th>
                <th class="text-end"><?= number_format($totals['total_transactions'] ?? 0) ?></th>
                <th class="text-end">$<?= number_format($totals['total_sales'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_tax'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_discount'] ?? 0, 2) ?></th>
                <th class="text-end">
                    $<?= $totals['total_transactions'] > 0 ? 
                        number_format($totals['total_sales'] / $totals['total_transactions'], 2) : '0.00' ?>
                </th>
                <th class="text-end">100%</th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Payment Method Distribution -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Payment Method Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Payment Method Performance</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php 
                        mysqli_data_seek($result, 0);
                        while ($row = mysqli_fetch_assoc($result)): 
                            $method_percent = $totals['total_transactions'] > 0 ? 
                                ($row['transaction_count'] / $totals['total_transactions']) * 100 : 0;
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span><?= ucfirst($row['payment_method']) ?></span>
                                    <span><?= number_format($method_percent, 1) ?>%</span>
                                </div>
                                <div class="progress mt-2" style="height: 10px;">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: <?= $method_percent ?>%;"
                                         aria-valuenow="<?= $method_percent ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">$<?= number_format($row['total_sales'], 2) ?></small>
                                    <small class="text-muted"><?= number_format($row['transaction_count']) ?> trans</small>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for chart
    const paymentData = {
        labels: [
            <?php 
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)) {
                echo "'" . ucfirst($row['payment_method']) . "',";
            }
            ?>
        ],
        datasets: [{
            data: [
                <?php 
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo $row['total_sales'] . ",";
                }
                ?>
            ],
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#3a3b45', '#2e59d9', '#17a673'
            ],
            hoverBackgroundColor: [
                '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'
            ],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }]
    };

    // Render pie chart
    const ctx = document.getElementById('paymentChart').getContext('2d');
     new Chart(ctx, {
        type: 'doughnut',
        data: paymentData,
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.chart.getDatasetMeta(0).total;
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>