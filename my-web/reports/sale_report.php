<?php
// reports/sale_report.php

// Query to get sales data with customer information
$query = "SELECT s.id, s.invoice_number, s.date, s.total, s.tax, s.discount, 
                 s.payment_method, s.payment_status, c.customer_name
          FROM sales s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE s.date BETWEEN ? AND ?
          ORDER BY s.date DESC";
 
$stmt = mysqli_prepare($conn, $query);
$end_date_full = $end_date . ' 23:59:59'; // Include full end day
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total sales for period
$total_query = "SELECT SUM(total) as total_sales, 
                       SUM(tax) as total_tax, 
                       SUM(discount) as total_discount
                FROM sales
                WHERE date BETWEEN ? AND ?";
$total_stmt = mysqli_prepare($conn, $total_query);
mysqli_stmt_bind_param($total_stmt, "ss", $start_date, $end_date_full);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$totals = mysqli_fetch_assoc($total_result);
?>

<div class="report-table">
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Tax</th>
                <th>Discount</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                    <td><?= htmlspecialchars($row['customer_name'] ?: 'Walk-in') ?></td>
                    <td class="text-end">$<?= number_format($row['total'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['tax'], 2) ?></td>
                    <td class="text-end">$<?= number_format($row['discount'], 2) ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['payment_method'])) ?></td>
                    <td>
                        <span class="badge 
                            <?= $row['payment_status'] == 'paid' ? 'bg-success' : 
                               ($row['payment_status'] == 'pending' ? 'bg-warning' : 
                               ($row['payment_status'] == 'partial' ? 'bg-info' : 'bg-danger')) ?>">
                            <?= ucfirst($row['payment_status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-group-divider">
            <tr>
                <th colspan="3" class="text-end">Totals:</th>
                <th class="text-end">$<?= number_format($totals['total_sales'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_tax'] ?? 0, 2) ?></th>
                <th class="text-end">$<?= number_format($totals['total_discount'] ?? 0, 2) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Sales:</span>
                        <strong>$<?= number_format($totals['total_sales'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Tax:</span>
                        <strong>$<?= number_format($totals['total_tax'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Discount:</span>
                        <strong>$<?= number_format($totals['total_discount'] ?? 0, 2) ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Net Sales:</span>
                        <strong>$<?= number_format(($totals['total_sales'] - $totals['total_discount']) ?? 0, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>