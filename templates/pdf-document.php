<?php
/** @var array $doc */
if (! defined('ABSPATH')) {
    exit;
}


$is_invoice = ('invoice' === $doc['type']);
$title      = $is_invoice ? 'INVOICE' : 'QUOTATION';
$date_label = $is_invoice ? 'Due Date' : 'Valid Until';
$date_value = $is_invoice ? $doc['due_date'] : $doc['valid_until'];

$money = function ($n) use ($doc) {
    return esc_html($doc['currency'] . ' ' . number_format((float) $n, 2));
};

$subtotal = 0;
foreach ($doc['items'] as $item) {
    $line_disc = isset($item['discount']) ? (float) $item['discount'] : 0;
    $subtotal += $item['qty'] * $item['price'] * (1 - $line_disc / 100);
}
$disc_pct = isset($doc['discount']) ? (float) $doc['discount'] : 0;
$discount = $subtotal * $disc_pct / 100;
$taxable  = $subtotal - $discount;
$tax      = $taxable * ($doc['tax_rate'] / 100);
$total    = $taxable + $tax;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 15mm 14mm 18mm 14mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            /* has wide Unicode coverage in both libs */
            font-size: 10px;
            color: #333333;
            line-height: 1.5;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            vertical-align: top;
        }

        .accent {
            color: #1f4e79;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #777777;
        }

        /* Header */
        .header td {
            padding-bottom: 12px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1f4e79;
        }

        .doc-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f4e79;
            letter-spacing: 2px;
        }

        .header-line {
            border-bottom: 3px solid #1f4e79;
        }

        /* Meta + bill to */
        .meta-table td {
            padding: 2px 0;
        }

        .meta-label {
            color: #777777;
            width: 90px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #1f4e79;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #cccccc;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        /* Line items */
        .items {
            margin-top: 18px;
        }

        .items thead th {
            background-color: #1f4e79;
            color: #ffffff;
            padding: 7px 8px;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
        }

        .items thead th.num {
            text-align: right;
        }

        .items tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e3e3e3;
        }

        .items tbody tr.alt td {
            background-color: #f6f9fc;
        }

        .items td.num {
            text-align: right;
            white-space: nowrap;
        }

        /* Totals */
        .totals-wrap {
            margin-top: 12px;
        }

        .totals td {
            padding: 5px 8px;
        }

        .totals .label {
            color: #555555;
        }

        .totals .grand td {
            background-color: #1f4e79;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 8px;
        }

        /* Notes */
        .notes {
            margin-top: 24px;
            padding: 10px;
            background-color: #f6f9fc;
            border-left: 3px solid #1f4e79;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* Keep rows and totals from splitting across pages */
        tr,
        .notes {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table class="header">
        <tr>
            <td style="width:60%;">
                <div class="company-name"><?php echo esc_html($doc['company']['name']); ?></div>
                <div class="muted">
                    <?php echo nl2br(esc_html($doc['company']['address'])); ?><br>
                    <?php echo esc_html($doc['company']['email']); ?> &middot; <?php echo esc_html($doc['company']['phone']); ?>
                </div>
            </td>
            <td class="right" style="width:40%;">
                <div class="doc-title"><?php echo esc_html($title); ?></div>
                <div class="muted"># <?php echo esc_html($doc['number']); ?></div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="header-line"></td>
        </tr>
    </table>

    <!-- BILL TO + META -->
    <table style="margin-top:16px;">
        <tr>
            <td style="width:55%;">
                <div class="section-title"><?php echo $is_invoice ? 'Bill To' : 'Prepared For'; ?></div>
                <strong><?php echo esc_html($doc['client']['name']); ?></strong><br>
                <?php echo nl2br(esc_html($doc['client']['address'])); ?><br>
                <?php echo esc_html($doc['client']['email']); ?>
            </td>
            <td style="width:5%;"></td>
            <td style="width:40%;">
                <div class="section-title">Details</div>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label"><?php echo $is_invoice ? 'Invoice Date' : 'Quote Date'; ?></td>
                        <td class="right"><?php echo esc_html($doc['date']); ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label"><?php echo esc_html($date_label); ?></td>
                        <td class="right"><?php echo esc_html($date_value); ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Reference</td>
                        <td class="right"><?php echo esc_html($doc['reference']); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- LINE ITEMS (thead repeats on every page) -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:37%;">Description</th>
                <th class="num" style="width:8%;">Qty</th>
                <th class="num" style="width:17%;">Unit Price</th>
                <th class="num" style="width:10%;">Disc %</th>
                <th class="num" style="width:23%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doc['items'] as $i => $item) :
                $ld = isset($item['discount']) ? (float) $item['discount'] : 0; ?>
                <tr class="<?php echo ($i % 2) ? 'alt' : ''; ?>">
                    <td><?php echo (int) ($i + 1); ?></td>
                    <td>
                        <strong><?php echo esc_html($item['name']); ?></strong>
                        <?php if (! empty($item['desc'])) : ?>
                            <br><span class="muted"><?php echo esc_html($item['desc']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?php echo esc_html($item['qty']); ?></td>
                    <td class="num"><?php echo $money($item['price']); ?></td>
                    <td class="num"><?php echo $ld ? esc_html($ld) . '%' : '-'; ?></td>
                    <td class="num"><?php echo $money($item['qty'] * $item['price'] * (1 - $ld / 100)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTALS (outer table pushes the totals to the right; works in both libs) -->
    <table class="totals-wrap">
        <tr>
            <td style="width:55%;"></td>
            <td style="width:45%;">
                <table class="totals">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="right"><?php echo $money($subtotal); ?></td>
                    </tr>
                    <?php if ($discount > 0) : ?>
                        <tr>
                            <td class="label">Discount (<?php echo esc_html($disc_pct); ?>%)</td>
                            <td class="right">- <?php echo $money($discount); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="label">Tax (<?php echo esc_html($doc['tax_rate']); ?>%)</td>
                        <td class="right"><?php echo $money($tax); ?></td>
                    </tr>
                    <tr class="grand">
                        <td><?php echo $is_invoice ? 'Total Due' : 'Total Estimate'; ?></td>
                        <td class="right"><?php echo $money($total); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- NOTES -->
    <div class="notes">
        <div class="notes-title"><?php echo $is_invoice ? 'Payment Terms' : 'Terms & Conditions'; ?></div>
        <?php echo nl2br(esc_html($doc['notes'])); ?>
    </div>

</body>

</html>