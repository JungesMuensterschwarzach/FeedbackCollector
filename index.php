<?php
	try {
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
				"INSERT INTO feedback(important, good, improvable) VALUES(?, ?, ?)"
			);
			$stmt->bind_param("sss", $_POST["important"], $_POST["good"], $_POST["improvable"]);
	
			if ($stmt->execute() === false) {
				$stmt->close();
				throw new Exception("Bitte melde bei 'lucas.kinne@pfarrei-meiningen.de'!");
			}
			$stmt->close();
	
			$alert = array(
				"level" => "Erfolg!",
				"message" => "Dein Feedback wurde gespeichert und wird nach dem Kurs in der Kursleitung besprochen.<br/>"
					. "Schau dir am <strong>Ende dieser Seite</strong> an, wie die anderen Teilnehmer den Kurs wahrgenommen haben!",
				"type" => "success"
			);
			unset($_POST);
		}

		$stmt = $mysqli->prepare(
			"SELECT important, good, improvable 
			 FROM feedback"
		);

		if ($stmt->execute() === false) {
			$stmt->close();
			throw new Exception("Bitte melde bei 'lucas.kinne@pfarrei-meiningen.de'!");
		}

		$res = $stmt->get_result();
		$feedback = array(
			"important" => array(),
			"good" => array(),
			"improvable" => array()
		);
		while ($row = $res->fetch_assoc()) {
			array_push($feedback["important"], $row["important"]);
			array_push($feedback["good"], $row["good"]);
			array_push($feedback["improvable"], $row["improvable"]);
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
					<img id="logo" class="d-inline-block m-2" src="logo.png" height="100" width="100"
						onclick="window.location.href = 'https://app.junges-muensterschwarzach.de';"/>
				</div>
				<hr>
				<p>Mit diesem Formular kannst du uns ganz anonym dein <strong>Feedback zu unserem Online-Osterkurs 2021</strong> zukommen lassen.</p>
				<p>Diese Seite ist quasi die digitale Alternative zu den üblichen Feedback-Wänden, die ihr von den Präsenzkursen vielleicht kennt.</p>
				<p>Wir werden nach dem Kurs in der Kursleitung über dein Feedback sprechen und es bei der Planung des kommmenden Pfingstkurses berücksichtigen.</p>
				<p>Beachte bitte, <strong>keine personenbezogenen Daten</strong> einzusenden, denn dein Feedback wird anschließend automatisch und unmoderiert am Ende dieser Seite für alle Teilnehmer sichtbar aufgelistet.</p>
				<hr>
				<form name="form" method="POST" class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-12" for="important"><strong>Was war dir beim Osterkurs wichtig?</strong></label>
						<div class="col-12">
							<textarea name="important" class="form-control"  
								rows="8"><?php if (isset($_POST["important"]) === true) echo($_POST["important"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="good"><strong>Was fandest du am Kurs gelungen?</strong></label>
						<div class="col-12">
							<textarea name="good" class="form-control"  
								rows="8"><?php if (isset($_POST["good"]) === true) echo($_POST["good"]);?></textarea>
						</div>
					</div>
					<div class="form-group mt-4">
						<label class="control-label col-12" for="improvable"><strong>Was hat dich gestört oder irritiert?</strong></label>
						<div class="col-12">
							<textarea name="improvable" class="form-control"  
								rows="8"><?php if (isset($_POST["improvable"]) === true) echo($_POST["improvable"]);?></textarea>
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
				<h4 class="mt-4">So fanden andere Teilnehmer den Kurs</h4>
				<p class="mt-4"><strong>Was war dir beim Osterkurs wichtig?</strong></p>
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
				<p class="mt-4"><strong>Was hat dich gestört oder irritiert?</strong></p>
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
			</div>
		</div>
	</body>
</html>