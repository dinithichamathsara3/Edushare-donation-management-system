<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect(user()['role'] . '/');
$m = db();
$stat = ['Items shared' => (int) $m->query("SELECT COUNT(*) FROM items WHERE status IN ('approved','reserved','donated')")->fetchColumn(), 'Items passed on' => (int) $m->query("SELECT COUNT(*) FROM items WHERE status='donated'")->fetchColumn(), 'Donors' => (int) $m->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn(), 'Verified institutions' => (int) $m->query("SELECT COUNT(*) FROM institutions WHERE verification_status='approved'")->fetchColumn(), 'Completed donations' => (int) $m->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn(),];
/* PICTURE SLOT: drop a file with the given name into assets/img/ and it appears automatically. */
function slot(string $file, string $hint, string $cls = '') : string {
    return '<div class="cover ph ' . $cls . '"><img src="' . url('assets/img/' . $file) . '" alt="" onerror="this.remove()"><small style="position:absolute;opacity:.6">' . e($hint) . '</small></div>';
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            <?= SITE_NAME ?>
            · Share books and education items
        </title>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    </head>
    <body>
        <header class="pub-nav">
            <a class="brand" href="<?= url() ?>">
                <span class="logo">
                    B
                </span>
                <?= SITE_NAME ?>
            </a>
            <nav>
                <a href="<?= url('about.php') ?>">
                    About
                </a>
                <a href="#how">
                    How it works
                </a>
                <a href="<?= url('browse.php') ?>">
                    Browse donations
                </a>
                <a href="<?= url('needs.php?urgent=1') ?>">
                    Urgent needs
                </a>
                <a href="<?= url('contact.php') ?>">
                    Contact
                </a>
                <a href="<?= url('login.php') ?>">
                    Log in
                </a>
                <a class="btn amber" href="<?= url('register.php') ?>">
                    Join free
                </a>
            </nav>
        </header>
        <section class="hero">
            <div>
                <h1>
                    Give Knowledge. Give Opportunity.
                </h1>
                <p>
                    Connect your unused educational items with schools and educational institutions that need them.
                </p>
                <div class="acts">
                    <a class="btn amber" href="<?= url('register.php') ?>">
                        Start sharing
                    </a>
                    <a class="btn sec" href="<?= url('browse.php') ?>">
                        Find donations
                    </a>
                    <a class="btn sec" href="<?= url('register.php') ?>">
                        Register institution
                    </a>
                </div>
            </div>
            <!-- HERO PICTURE: save a photo as assets/img/hero.jpg (portrait works best, ~1000×1100) -->
            <div class="hero-art">
                <?= '<img src="'.url('assets/img/learning.svg').'" alt="Books representing educational donations" style="width:100%;border-radius:24px">' ?>
            </div>
        </section>
        <section class="sec" id="how">
            <h2>
                Three steps from shelf to recipient
            </h2>
            <p class="lead">
                Register, donate or request, get matched, and complete your donation. Institutions are verified before they can claim resources.
            </p>
            <div class="steps">
                <!-- STEP PICTURES: assets/img/step1.jpg, step2.jpg, step3.jpg (16:9) -->
                <div class="step">
                    
                    <div class="t">
                        <h3>
                            Donors list an item
                        </h3>
                        <p class="muted">
                            Add a title, category, condition, quantity and photo. Choose donation or exchange.
                        </p>
                    </div>
                </div>
                <div class="step">
                    
                    <div class="t">
                        <h3>
                            Recipients search and request
                        </h3>
                        <p class="muted">
                            Filter by category, kind, condition or type, then send a request with one click.
                        </p>
                    </div>
                </div>
                <div class="step">
                    
                    <div class="t">
                        <h3>
                            Donor approves, item moves on
                        </h3>
                        <p class="muted">
                            Both sides get notified at every step until the hand-over is done.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <section class="sec band">
            <div class="stats" style="max-width:900px">
                <?php
                foreach ($stat as $k => $v) :
                ?>
                    <div class="stat">
                        <b>
                            <?= $v ?>
                        </b>
                        <span>
                            <?= e($k) ?>
                        </span>
                    </div>
                <?php
                endforeach;
                ?>
            </div>
        </section>
        <section class="sec">
            <h2>
                Urgent needs
            </h2>
            <?php
            $urgent = $m->query("SELECT n.*,u.institution,u.city FROM requirements n JOIN users u ON u.id=n.recipient_id JOIN institutions v ON v.user_id=u.id WHERE n.status='open' AND n.urgency_status='approved' AND v.verification_status='approved' AND u.status='active' ORDER BY n.required_date LIMIT 6")->fetchAll();
            if (!$urgent) echo '<p>No approved urgent requirements yet.</p>';
            foreach ($urgent as $n) :
            ?>
                <div class="card">
                    <strong>
                        <?= e($n['institution'].' · '.$n['title']) ?>
                    </strong>
                    <p>
                        <?= e($n['city']) ?>
                        ·
                        <?= remaining($n) ?>
                        still needed · Due
                        <?= e($n['required_date']) ?>
                    </p>
                    <a class="btn" href="<?= url('needs.php?urgent=1') ?>">
                        Donate now
                    </a>
                </div>
            <?php
            endforeach;
            ?>
        </section>
        <section class="sec">
            <h2>
                Nearby needs
            </h2>
            <form action="<?= url('needs.php') ?>">
                <label>
                    Select city or district
                    <input name="location" required>
                </label>
                <button class="btn">
                    Find schools
                </button>
            </form>
        </section>
        <section class="sec">
            <h2>
                Recently available donations
            </h2>
            <div class="shelf">
                <?php
                $recent = $m->query("SELECT b.*,c.name category,u.name donor FROM items b JOIN categories c ON c.id=b.category_id JOIN users u ON u.id=b.donor_id WHERE b.status='approved' AND u.status='active' ORDER BY b.created_at DESC LIMIT 6")->fetchAll();
                if (!$recent) echo '<p>No available donations yet.</p>';
                foreach ($recent as $b) {
                    $b['quantity'] = available($b);
                    echo item_card($b, '<a href="' . url('browse.php') . '">Browse donations</a>');
                }
                ?>
            </div>
        </section>
        <footer class="foot">
            <span>
                ©
                <?= date('Y') ?>
                <?= SITE_NAME ?>
                — CS12012 Web Development project
            </span>
            <span>
                Book &amp; Education Items Donation Management System
            </span>
        </footer>
    </body>
</html>
