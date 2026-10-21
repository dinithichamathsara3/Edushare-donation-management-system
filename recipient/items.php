<?php
$title = 'Browse items';
require_once __DIR__ . '/../includes/functions.php';
require_receiver();
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<form id="filters" class="filters" autocomplete="off">
    <input name="q" placeholder="Search by name, author or brand" aria-label="Search">
    <select name="category" aria-label="Category">
        <option value="">
            All categories
        </option>
        <?php
        foreach ($cats as $c) :
        ?>
            <option value="<?= $c['id'] ?>">
                <?= e($c['name']) ?>
            </option>
        <?php
        endforeach;
        ?>
    </select>
    <select name="kind" aria-label="Kind">
        <option value="">
            Books &amp; items
        </option>
        <option value="book">
            Books only
        </option>
        <option value="education_item">
            Education items only
        </option>
    </select>
    <select name="type" aria-label="Type">
        <option value="">
            Donate or exchange
        </option>
        <option value="donate">
            Donation
        </option>
        <option value="exchange">
            Exchange
        </option>
    </select>
    <select name="cond" aria-label="Condition">
        <option value="">
            Any condition
        </option>
        <option>
            New
        </option>
        <option>
            Good
        </option>
        <option>
            Fair
        </option>
    </select>
    <?php
    foreach (["subject" => "Subject", "education_level" => "Education level", "location" => "City / district", "quantity" => "Minimum quantity", "availability_date" => "Available by"] as $n => $label) echo field($n, $label, $n === "quantity" ? "number" : ($n === "availability_date" ? "date" : "text"));
    ?>
    <select name="radius" aria-label="Distance">
        <option value="0">
            Any distance
        </option>
        <option value="5">
            Within 5 km
        </option>
        <option value="10">
            Within 10 km
        </option>
        <option value="25">
            Within 25 km
        </option>
    </select>
</form>
<p class="small muted">
    Distance filters use the coordinates saved in your profile and the donation listing. Both locations need coordinates.
</p>
<div class="shelf" id="results">
</div>
<script>document.getElementById('filters').dispatchEvent(new Event('input'));</script>
<?php
require __DIR__ . '/../includes/footer.php';
?>
