<?php

$isTeam = (bool)getenv('TEAM');

$meta = [
	'title' => 'Feedback-Sammler' . ($isTeam ? ' für die Kursleitung' : ''),
	'description' => $isTeam ?
		'<p>Mit diesem Formular kannst du anonym dein <strong>Kurs-Feedback</strong> einsenden.</p>
		<p>Br. Wolfgang wird im Nachgang dein Feedback auswerten.</p>'
		:
		'<p>Mit diesem Formular kannst du uns ganz anonym dein <strong>Kurs-Feedback</strong> zukommen lassen.</p>
			<p>Wir werden nach dem Kurs in der Kursleitung über dein Feedback sprechen und es bei der Planung der kommenden Kurse berücksichtigen.</p>
			<p>Beachte bitte, <strong>keine personenbezogenen Daten</strong> einzusenden, denn dein Feedback wird anschließend automatisch und unmoderiert am Ende dieser Seite für alle Teilnehmer sichtbar aufgelistet.</p>',
	'qr' => 'qr' . ($isTeam ? '-team' : '') . '.png',
	'results' => 'So fanden andere ' . ($isTeam ? 'Kursleiter*innen' : 'Teilnehmer*innen') . ' den Kurs:',
	'success' => $isTeam ?
		"Dein Feedback wurde gespeichert.<br/>Schau dir am <strong><a href='#feedback'>Ende dieser Seite</a></strong> an, wie die anderen Kursleiter*innen den Kurs wahrgenommen haben!" : 
		"Dein Feedback wurde gespeichert und wird nach dem Kurs in der Kursleitung besprochen.<br/>Schau dir am <strong><a href='#feedback'>Ende dieser Seite</a></strong> an, wie die anderen Teilnehmer*innen den Kurs wahrgenommen haben!"
];
$feedback = [
	'important' => [
		'title' => 'Was war dir beim Kurs wichtig?',
		'description' => '(Bsp.: Gemeinschaft, Spielerunden, Freunde/Bekannte wiedersehen ...)',
		'values' => []
	],
	'good' => [
		'title' => 'Was fandest du am Kurs gelungen?',
		'description' => '(Bsp.: bestimmte Programmpunkte, offene Gesprächs- und Spieleabende, ...)',
		'values' => []
	],
	'improvable' => [
		'title' => 'Was hättest du dir (anders/noch) gewünscht?',
		'description' => '(Bsp.: mehr frei verfügbare Zeit, anderes Workshop-Angebot, ...)',
		'values' => []
	],
	'referrer' => [
		'title' => 'Wie bist du auf den Kurs aufmerksam geworden?',
		'description' => '(Bsp.: "Stammgast", Social-Media-Posts (Facebook, Instagram, ...), persönliche Werbung (Familie, Freunde, ...), ...)',
		'values' => []
	],
	'misc' => [
		'title' => 'Was möchtest du uns sonst noch loswerden?',
		'description' => '',
		'values' => []
	]
];

if ($isTeam) {
	unset($feedback['important']);
	unset($feedback['referrer']);
}

try {
	$secretsDir = getenv("JMFC_SECRETS") ? getenv("JMFC_SECRETS") : "/var/data/secrets/jmfc";
	$conDets = json_decode(file_get_contents($secretsDir . "/database.json"), true);
	$mysqli = new mysqli($conDets["server"], $conDets["username"], $conDets["password"], $conDets["database"]);
	$mysqli->set_charset($conDets["encoding"]);

	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		$stmt = $mysqli->prepare(
			"INSERT INTO feedback(important, good, improvable, referrer, misc) VALUES(?, ?, ?, ?, ?)"
		);
		$important = $_POST["important"] ?? '';
		$good = $_POST["good"] ?? '';
		$improvable = $_POST["improvable"] ?? '';
		$referrer = $_POST["referrer"] ?? '';
		$misc = $_POST["misc"] ?? '';
		$stmt->bind_param("sssss", $important, $good, $improvable, $referrer, $misc);

		if ($stmt->execute() === false) {
			$stmt->close();
			throw new Exception("Bitte melde dich bei <a href='mailto:junge@abtei-muensterschwarzach.de'>junge@abtei-muensterschwarzach.de</a>!");
		}
		$stmt->close();

		$alert = [
			"level" => "Erfolg!",
			"message" => $meta['success'],
			"type" => "success"
		];
		unset($_POST);
	}

	$stmt = $mysqli->prepare(
		"SELECT important, good, improvable, referrer, misc FROM feedback"
	);

	if ($stmt->execute() === false) {
		$stmt->close();
		throw new Exception("Bitte melde dich bei <a href='mailto:junge@abtei-muensterschwarzach.de'>junge@abtei-muensterschwarzach.de</a>!");
	}

	$res = $stmt->get_result();
	while ($row = $res->fetch_assoc()) {
		foreach ($feedback as $key => $attributes) {
			array_push($feedback[$key]['values'], $row[$key]);
		}
	}
	$stmt->close();
} catch (Exception $exc) {
	$alert = array(
		"level" => "Fehler!",
		"message" => $exc->getMessage(),
		"type" => "danger"
	);
} finally {
	unset($mysqli);
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
	<title><?= $meta['title'] ?></title>
	<meta name="author" content="Lucas 'Dherlou' Kinne">
	<meta charset="utf-8">
	<link rel="icon" href="favicon.png">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/stylesheet.css">
	<script src="js/bootstrap.bundle.min.js"></script>
</head>

<body>
	<div class="container">
		<?php if (isset($alert)) { ?>
			<div class="alert alert-<?php echo ($alert["type"]); ?>">
				<span><strong><?php echo ($alert["level"]); ?></strong> <?php echo ($alert["message"]); ?></span>
			</div>
		<?php } ?>
		<div class="jumbotron jmfc-background-color mt-4 p-3">
			<div class="d-flex justify-content-between align-items-center">
				<h1 class="d-inline-block m-2 jmfc-important"><?= $meta['title'] ?></h1>
				<div>
					<img id="qr" class="d-inline-block m-2" src="<?= $meta['qr'] ?>" height="100" width="100" />
					<img id="logo" class="d-inline-block m-2" src="logo.png" height="100" width="100" onclick="window.location.href = 'https://junges-muensterschwarzach.roth-familie.eu';" />
				</div>
			</div>
			<hr>
			<?= $meta['description'] ?>
			<hr>
			<form name="form" method="POST" class="form-horizontal">
				<?php foreach ($feedback as $key => $attributes) { ?>
				<div class="form-group">
					<label class="control-label col-12" for="<?= $key ?>">
						<strong><?= $attributes['title'] ?></strong>
						<?php if (!empty($attributes['description'])) { ?>
							<br /><span class="jmfc-examples"><?= $attributes['description'] ?></span>
						<?php } ?>
					</label>
					<div class="col-12">
						<textarea name="<?= $key ?>" class="form-control" rows="8"><?php if (isset($_POST[$key]) === true) echo ($_POST[$key]); ?></textarea>
					</div>
				</div>
				<?php } ?>
				<hr>
				<div class="form-group mt-4">
					<div class="col-12">
						<button id="submit" type="submit" class="btn btn-success">Absenden</button>
					</div>
				</div>
			</form>
			<hr>
			<h4 id="feedback" class="mt-4"><?= $meta['results'] ?></h4>
			<?php foreach ($feedback as $key => $attributes) { ?>
			<p class="mt-4"><strong><?= $attributes['title'] ?></strong></p>
			<ul>
				<?php
				foreach ($feedback[$key]['values'] as $output) {
					if (empty($output)) {
						continue;
					}
				?>
					<li><?php echo (nl2br(htmlspecialchars($output))); ?></li>
				<?php
				}
				?>
			</ul>
			<?php } ?>
		</div>
	</div>
</body>

</html>