<?php
/**
 * Elvárt változók:
 * $title
 * $message
 * $status  // success | cancel
 * $amount  // opcionális
 */

$st_back_url = isset($_SESSION['st_back_url']) ? $_SESSION['st_back_url']: $config->get('back_url') ;
$st_paysite_title = isset($_SESSION['st_paysite_title']) ? $_SESSION['st_paysite_title'] : $config->get('paysite_title');

\App\Support\AppLogger::get()->debug( "Resulthtml, session:" , array($_SESSION['st_back_url'] , $_SESSION['st_paysite_title'] )); 
\App\Support\AppLogger::get()->debug( "Resulthtml, variables:" , array($st_back_url, $st_paysite_title  )); 

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($st_paysite_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --success: #0_links: #635bff;
            --success: #22c55e;
            --error: #ef4444;
			--unknown: #fcb603; 
            --bg: #f6f9fc;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                         Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            text-align: center;
        }

        .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
        }

        .icon.success { background: var(--success); }
        .icon.cancel  { background: var(--error); }
		.icon.unknown  { background: var(--unknown); }

        h1 {
            font-size: 22px;
            margin: 0 0 12px;
        }

        p {
            font-size: 15px;
            color: var(--muted);
            margin: 0 0 20px;
            line-height: 1.5;
        }

        .amount {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

		button {
			width: 100%;
			padding: 12px;
			border: none;
			border-radius: 8px;
			background: #635bff;
			color: #fff;
			font-size: 15px;
			font-weight: 500;
			cursor: pointer;
		}

		button:hover {
			opacity: 0.95;
		}
	
        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            background: var(--links);
            color: #111827;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .btn.secondary {
            background: #e5e7eb;
            color: #111827;
        }
    </style>
</head>
<body>

<?php

$amount = $session->amount_total / 100;

if ($session->payment_status === 'paid') {
	$status = 'success';
	$icon = '✓';
	$message = "✅ Payment successfull / A fizetés sikeres!";

} else if ($session->payment_status === 'unpaid') {
	$status = 'cancel';
	$icon = '!';
	$message = "❌ The payment was cancelled / A fizetési folyamat megszakítva";	
		
} else {
	$status = 'unknown';
	$icon = '?';
	$message = "⏳ The payment not finalized yet / A fizetés még nem végleges";	
}



?>

<div class="container">
    <div class="card">

        <div class="icon <?= $status ?>">
            <?= $icon ?>
        </div>

        <h1><?= htmlspecialchars($st_paysite_title) ?></h1>

        <p><?= nl2br(htmlspecialchars($message)) ?></p>

        <?php if (!empty($amount)): ?>
            <div class="amount">
                Összeg: <?= htmlspecialchars($amount) ?> Ft
            </div>
        <?php endif; ?>

        <?php if ($status === 'success'): ?>
            <a href="<?= $st_back_url ?>" class="btn secondary">Vissza a weboldalra</a>
        <?php else: ?>
			<a href="#" onclick="history.back(); return false;" class="btn secondary">Újrapróbálom</a>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
