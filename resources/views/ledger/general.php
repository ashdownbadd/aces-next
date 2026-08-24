<?php
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$money=static fn(float $v):string=>'₱'.number_format($v,2,'.',',');
?>
<div class="ledger-page">
<header class="page-header">
<div>
<h1>General Ledger</h1>
<p>View posted transactions and running account balances.</p>
</div>
<div class="ledger-page__header-actions">
    <a href="/ledger" class="btn btn--secondary">Journal Vouchers</a>
    <a href="/ledger/general" class="btn btn--secondary">General Ledger</a>
    <a href="/ledger/trial-balance" class="btn btn--secondary">Trial Balance</a>
    <a href="/ledger/balance-sheet" class="btn btn--secondary">Financial Position</a>
    <a href="/ledger/income-statement" class="btn btn--secondary">Statement of Operations</a>
    <a href="/ledger/accounts" class="btn btn--secondary">Chart of Accounts</a>
</div>
</header>

<?php if(!empty($error)): ?>
<div class="alert alert--error"><?= $e($error) ?></div>
<?php endif; ?>

<form method="GET" action="/ledger/general" class="ledger-page__filters card">
<div class="ledger-page__filter-field">
<label for="gl-account">Account</label>
<select id="gl-account" class="input" name="account" required>
<option value="">Select an account</option>
<?php foreach($accounts as $account): ?>
<option value="<?= (int)$account['id'] ?>" <?= (int)($accountId??0)===(int)$account['id']?'selected':'' ?>>
<?= $e($account['account_code']) ?> — <?= $e($account['account_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="ledger-page__filter-field">
<label for="gl-from">From</label>
<input id="gl-from" class="input" type="date" name="date_from" value="<?= $e($dateFrom??'') ?>">
</div>

<div class="ledger-page__filter-field">
<label for="gl-to">To</label>
<input id="gl-to" class="input" type="date" name="date_to" value="<?= $e($dateTo??'') ?>">
</div>

<div class="ledger-page__filter-actions">
<button class="btn btn--secondary" type="submit">View Ledger</button>
<a href="/ledger/general" class="btn btn--outline">Clear</a>
</div>
</form>

<?php if($ledger!==null): ?>
<?php $account=$ledger['account']; ?>
<section class="ledger-page__summary-grid ledger-page__summary-grid--three">
<div class="card ledger-stat">
<span class="ledger-stat__label">Account</span>
<strong class="ledger-stat__value"><?= $e($account['account_code']) ?></strong>
<span class="ledger-stat__hint"><?= $e($account['account_name']) ?></span>
</div>
<div class="card ledger-stat">
<span class="ledger-stat__label">Opening Balance</span>
<strong class="ledger-stat__value"><?= $money((float)$ledger['opening_balance']) ?></strong>
<span class="ledger-stat__hint"><?= $e($account['normal_balance']) ?> normal balance</span>
</div>
<div class="card ledger-stat">
<span class="ledger-stat__label">Closing Balance</span>
<strong class="ledger-stat__value"><?= $money((float)$ledger['closing_balance']) ?></strong>
<span class="ledger-stat__hint"><?= count($ledger['rows']) ?> posted line(s)</span>
</div>
</section>

<section class="card ledger-detail__lines">
<div class="ledger-detail__section-header">
<h2><?= $e($account['account_code']) ?> — <?= $e($account['account_name']) ?></h2>
<p>Only Posted vouchers affect the General Ledger.</p>
</div>
<div class="ledger-detail__table-scroll">
<table class="table ledger-detail__table">
<thead><tr>
<th>Date</th><th>Reference</th><th>Particulars</th><th>Source</th>
<th class="ledger-detail__amount">Debit</th>
<th class="ledger-detail__amount">Credit</th>
<th class="ledger-detail__amount">Balance</th>
</tr></thead>
<tbody>
<?php if($ledger['rows']===[]): ?>
<tr><td colspan="7" class="ledger-page__empty">No posted transactions found for this account and date range.</td></tr>
<?php else: ?>
<?php foreach($ledger['rows'] as $row): ?>
<tr>
<td><?= $e($row['transaction_date']) ?></td>
<td><a href="/ledger/<?= (int)$row['voucher_id'] ?>"><?= $e($row['reference_number']) ?></a></td>
<td><?= $e($row['particulars']) ?><?php if(!empty($row['line_description'])): ?><span class="ledger-detail__account-name"><?= $e($row['line_description']) ?></span><?php endif; ?></td>
<td><?= $e($row['source_type']??'—') ?><?php if((int)($row['source_id']??0)>0): ?> #<?= (int)$row['source_id'] ?><?php endif; ?></td>
<td class="ledger-detail__amount"><?= (float)$row['debit']>0?$money((float)$row['debit']):'—' ?></td>
<td class="ledger-detail__amount"><?= (float)$row['credit']>0?$money((float)$row['credit']):'—' ?></td>
<td class="ledger-detail__amount"><?= $money((float)$row['running_balance']) ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
<?php endif; ?>
</div>
