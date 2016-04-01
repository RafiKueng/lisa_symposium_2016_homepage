
<h1>Registration</h1>

<p>
    The conference fee is <?=$baseFeeReduced+$dinnerFee;?>.&mdash; CHF for early bookers,
    until  <?=$reducedLimitDate->format($date_fstr);?>.
    Please remember to book your hotel in time as well!
    The regular fee is <?= $baseFeeRegular+$dinnerFee ?>.&mdash; CHF.
<p>

</p>
    This fee includes:
</p>
<ul>
    <li>Attendance fee</li>
    <li>Printed proceedings</li>
    <li>Coffee breaks</li>
    <li>Conference dinner</li>
    <li>Local transportation (24h public transport; except airport transfer)</li>
</ul>

<p>
    After sending this form, you will receive further instructions about payment (by bank) and your personal login link.
    Please keep it save.
    You can get a new one <a href="">here</a>.
    <br>
    For any special requests, please register anyways and contact us by email.

</p>

<?php require "items/registration_form.php"; ?>