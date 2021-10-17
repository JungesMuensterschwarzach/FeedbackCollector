<?php
	try {
		$feedback = array(
			"important" => array(),
			"good" => array(),
			"improvable" => array(),
			"referrer" => array(),
			"misc" => array()
		);

		$secretsDir = getenv("JMFC_SECRETS") ? getenv("JMFC_SECRETS") : "/var/data/secrets/jmfc";
		$conDets = json_decode(file_get_contents($secretsDir."/database.json"), true);
		$mysqli = new mysqli(
			$conDets["server"],
			$conDets["username"],
			$conDets["password"],
			$conDets["database"]
		);
		$mysqli->set_charset($conDets["encoding"]);

		if ($_SERVER["REQUEST_METHOD"] === "POST") {
			$stmt = $mysqli->prepare(
				"INSERT INTO feedback(important, good, improvable, referrer, misc) VALUES(?, ?, ?, ?, ?)"
			);
			$stmt->bind_param("sssss", $_POST["important"], $_POST["good"], $_POST["improvable"], $_POST["referrer"], $_POST["misc"]);
	
			if ($stmt->execute() === false) {
				$stmt->close();
				throw new Exception("Bitte melde dich bei <a href='mailto:junge@abtei-muensterschwarzach.de'>junge@abtei-muensterschwarzach.de</a>!");
			}
			$stmt->close();
	
			$alert = array(
				"level" => "Erfolg!",
				"message" => "Dein Feedback wurde gespeichert und wird nach dem Kurs in der Kursleitung besprochen.<br/>"
					. "Schau dir am <strong><a href='#feedback'>Ende dieser Seite</a></strong> an, wie die anderen Teilnehmer den Kurs wahrgenommen haben!",
				"type" => "success"
			);
			unset($_POST);
		}

		$stmt = $mysqli->prepare(
			"SELECT important, good, improvable, referrer, misc 
			 FROM feedback"
		);

		if ($stmt->execute() === false) {
			$stmt->close();
			throw new Exception("Bitte melde dich bei <a href='mailto:junge@abtei-muensterschwarzach.de'>junge@abtei-muensterschwarzach.de</a>!");
		}

		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			array_push($feedback["important"], $row["important"]);
			array_push($feedback["good"], $row["good"]);
			array_push($feedback["improvable"], $row["improvable"]);
			array_push($feedback["referrer"], $row["referrer"]);
			array_push($feedback["misc"], $row["misc"]);
		}
		$stmt->close();
	} catch(Exception $exc) {
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
		<title>Junges Münsterschwarzach - Feedback-Sammler</title>
		<meta name="author" content="Lucas 'Pad' Kinne">
		<meta charset="utf-8">
		<link rel="icon" href="favicon.png">
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/stylesheet.css">
		<script src="js/bootstrap.bundle.min.js"></script>
	</head>
	<body>
		<div class="container">
			<?php if (isset($alert)) { ?>
				<div class="alert alert-<?php echo($alert["type"]);?>">
					<span><strong><?php echo($alert["level"]);?></strong> <?php echo($alert["message"]); ?></span>
				</div>
			<?php } ?>
			<div class="jumbotron jmfc-background-color mt-4 p-3">
				<div class="d-flex justify-content-between align-items-center">
					<h1 class="d-inline-block m-2 jmfc-important">Feedback-Sammler</h1>
					<div>
						<img id="qr" class="d-inline-block m-2" src="qr.png" height="100" width="100"/>
						<img id="logo" class="d-inline-block m-2" src="logo.png" height="100" width="100"
							onclick="window.location.href = 'https://app.junges-muensterschwarzach.de';"/>
					</div>
				</div>
				<hr>
				<p>Mit diesem Formular kannst du uns ganz anonym dein <strong>Feedback zu unserem Silvesterkurs 2021/2022</strong> zukommen lassen.</p>
				<p>Diese Seite ist die digitale Alternative zu den Feedback-Wänden vor Ort.</p>
				<p>Wir werden nach dem Kurs in der Kursleitung über dein Feedback sprechen und es bei der Planung der kommenden Kurse berücksichtigen.</p>
				<p>Beachte bitte, <strong>keine personenbezogenen Daten</strong> einzusenden, denn dein Feedback wird anschließend automatisch und unmoderiert am Ende dieser Seite für alle Teilnehmer sichtbar aufgelistet.</p>
				<hr>
				<form name="form" method="POST" class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-12" for="important">
							<strong>Was war dir beim Kurs wichtig?</strong><br/>
							<span class="jmfc-examples">(Bsp.: Gemeinschaft, Spielerunden, Freunde/Bekannte wiedersehen ...)</span>
						</label>
						<div class="col-12">
							<textarea name="important" class="form-control"  
								rows="8"><?php if (isset($_POST["important"]) === true) echo($_POST["important"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="good">
							<strong>Was fandest du am Kurs gelungen?</strong><br/>
							<span class="jmfc-examples">(Bsp.: bestimmte Programmpunkte, Corona-konforme Durchführung in Präsenz, diese Feedback-Wand (😂), ...) </span>
						</label>
						<div class="col-12">
							<textarea name="good" class="form-control"  
								rows="8"><?php if (isset($_POST["good"]) === true) echo($_POST["good"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="improvable">
							<strong>Was hättest du dir (anders/noch) gewünscht?</strong><br/>
							<span class="jmfc-examples">(Bsp.: mehr frei verfügbare Zeit, anderes Workshop-Angebot, Jesajas Witze (😂), ...)</span>
						</label>
						<div class="col-12">
							<textarea name="improvable" class="form-control"  
								rows="8"><?php if (isset($_POST["improvable"]) === true) echo($_POST["improvable"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="referrer">
							<strong>Wie bist du auf den Kurs aufmerksam geworden?</strong><br/>
							<span class="jmfc-examples">(Bsp.: "Stammgast", Social-Media-Posts (Facebook, Instagram, ...), persönliche Werbung (Freunde, Digitale Piazza, ...), ...)</span>
						</label>
						<div class="col-12">
							<textarea name="referrer" class="form-control"  
								rows="8"><?php if (isset($_POST["referrer"]) === true) echo($_POST["referrer"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="referrer">
							<strong>Was möchtest du uns sonst noch sagen?</strong>
						</label>
						<div class="col-12">
							<textarea name="misc" class="form-control"  
								rows="8"><?php if (isset($_POST["misc"]) === true) echo($_POST["misc"]);?></textarea>
						</div>
					</div>
					<hr>
					<div class="form-group mt-4">
						<div class="col-12">
							<button id="submit" type="submit" class="btn btn-success">Absenden</button>
						</div>
					</div>
				</form>
				<hr>
				<h4 id="feedback" class="mt-4">So fanden andere Teilnehmer den Kurs</h4>
				<p class="mt-4"><strong>Was war dir beim Kurs wichtig?</strong></p>
				<ul>
				<?php
					foreach ($feedback["important"] as $important) {
						if (empty($important)) {
							continue;
						}
				?>
						<li><?php echo(nl2br(htmlspecialchars($important))); ?></li>
				<?php
					}
				?>
				</ul>
				<p class="mt-4"><strong>Was fandest du am Kurs gelungen?</strong></p>
				<ul>
				<?php
					foreach ($feedback["good"] as $good) {
						if (empty($good)) {
							continue;
						}
				?>
						<li><?php echo(nl2br(htmlspecialchars($good))); ?></li>
				<?php
					}
				?>
				</ul>
				<p class="mt-4"><strong>Was hättest du dir (anders/noch) gewünscht?</strong></p>
				<ul>
				<?php
					foreach ($feedback["improvable"] as $improvable) {
						if (empty($improvable)) {
							continue;
						}
				?>
						<li><?php echo(nl2br(htmlspecialchars($improvable))); ?></li>
				<?php
					}
				?>
				</ul>
				<p class="mt-4"><strong>Wie bist du auf den Kurs aufmerksam geworden?</strong></p>
				<ul>
				<?php
					foreach ($feedback["referrer"] as $referrer) {
						if (empty($referrer)) {
							continue;
						}
				?>
						<li><?php echo(nl2br(htmlspecialchars($referrer))); ?></li>
				<?php
					}
				?>
				</ul>
				<p class="mt-4"><strong>Was möchtest du uns sonst noch sagen?</strong></p>
				<ul>
				<?php
					foreach ($feedback["misc"] as $misc) {
						if (empty($misc)) {
							continue;
						}
				?>
						<li><?php echo(nl2br(htmlspecialchars($misc))); ?></li>
				<?php
					}
				?>
				</ul>
			</div>
		</div>
	</body>
</html>