<?php
require_once __DIR__ . '/includes/functions.php';
$title = 'Browse donations';
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
require __DIR__ . '/includes/public_header.php';
?>
<h1>
    Available educational donations
</h1>
<form id="filters" class="filters">
    <input name="q" placeholder="Item name / brand">
    <select name="category">
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
    <select name="cond">
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
    foreach (['subject' => 'Subject', 'education_level' => 'Grade', 'location' => 'Location', 'quantity' => 'Minimum quantity', 'availability_date' => 'Available by'] as $n => $label) echo field($n, $label, $n === 'quantity' ? 'number' : ($n === 'availability_date' ? 'date' : 'text'));
    ?>
    <button class="btn">
        Search
    </button>
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
<p>
    <a href="<?= url('login.php') ?>">
        Log in as an approved institution to claim an item.
    </a>
</p>
<p class="small muted">
    Distance filters use the coordinates saved in your profile and the donation listing. Both locations need coordinates.
</p>
<div class="shelf" id="results">
</div>
<?php
require __DIR__ . '/includes/public_footer.php';
?>
