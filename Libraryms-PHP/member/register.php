<?php
require "../db_connect.php";
require "../message_display.php";
require "../header.php";
?>

<html>
	<head>
		<title>LMS</title>
		<link rel="stylesheet" type="text/css" href="../css/global_styles.css">
		<link rel="stylesheet" type="text/css" href="../css/form_styles.css">
		<link rel="stylesheet" href="css/register_style.css">
	</head>
	<body>
		<form class="cd-form" method="POST" action="#">
			<center><legend>Member Registration</legend><p>Please fill up the form below:</p></center>
			
			<div class="error-message" id="error-message" style="display: none;">
				<p id="error"></p>
			</div>

			<div class="icon">
				<input class="m-name" type="text" name="m_name" placeholder="Full Name" required />
			</div>

			<div class="icon">
				<input class="m-email" type="email" name="m_email" id="m_email" placeholder="Email" required />
			</div>
			
			<div class="icon">
				<input class="m-user" type="text" name="m_user" id="m_user" placeholder="Username" required />
			</div>
			
			<div class="icon">
				<input class="m-pass" type="password" name="m_pass" placeholder="Password" required />
			</div>
			
			<div class="icon">
				<input class="m-balance" type="number" name="m_balance" id="m_balance" placeholder="Initial Balance" required />
			</div>
			
			<br />
			<input type="submit" name="m_register" value="Submit" />
		</form>
	</body>
	
	<?php
	if (isset($_POST['m_register'])) {
		// Input validation
		$m_name = filter_input(INPUT_POST, 'm_name', FILTER_SANITIZE_STRING);
		$m_email = filter_input(INPUT_POST, 'm_email', FILTER_VALIDATE_EMAIL);
		$m_user = filter_input(INPUT_POST, 'm_user', FILTER_SANITIZE_STRING);
		$m_pass = filter_input(INPUT_POST, 'm_pass', FILTER_SANITIZE_STRING);
		$m_balance = filter_input(INPUT_POST, 'm_balance', FILTER_VALIDATE_FLOAT);

		if ($m_balance < 500) {
			echo '<script>document.getElementById("error").innerHTML = "' . htmlspecialchars("Initial balance must be at least 500 in order to create an account") . '"; document.getElementById("error-message").style.display = "block"; document.getElementById("m_balance").className += " error-field";</script>';
		} else {
			// Use prepared statements with parameterized queries
			$stmt = $con->prepare("SELECT username FROM member WHERE username =? UNION SELECT username FROM pending_registrations WHERE username =?");
			$stmt->bind_param("ss", $m_user, $m_user);
			$stmt->execute();
			$result = $stmt->get_result();
			if (mysqli_num_rows($result)!= 0) {
				echo '<script>document.getElementById("error").innerHTML = "' . htmlspecialchars("The username you entered is already taken") . '"; document.getElementById("error-message").style.display = "block"; document.getElementById("m_user").className += " error-field";</script>';
			} else {
				$stmt = $con->prepare("SELECT email FROM member WHERE email =? UNION SELECT email FROM pending_registrations WHERE email =?");
				$stmt->bind_param("ss", $m_email, $m_email);
				$stmt->execute();
				$result = $stmt->get_result();
				if (mysqli_num_rows($result)!= 0) {
					echo '<script>document.getElementById("error").innerHTML = "' . htmlspecialchars("An account is already registered with that email") . '"; document.getElementById("error-message").style.display = "block"; document.getElementById("m_email").className += " error-field";</script>';
				} else {
					$stmt = $con->prepare("INSERT INTO pending_registrations(username, password, name, email, balance) VALUES(?,?,?,?,?)");
					$stmt->bind_param("ssssd", $m_user, sha1($m_pass), $m_name, $m_email, $m_balance);
					if ($stmt->execute()) {
						echo '<script>document.getElementById("error").innerHTML = "' . htmlspecialchars("Details submitted, soon you\'ll will be notified after verifications!") . '"; document.getElementById("error-message").className = "success-message";</script>';
					} else {
						echo '<script>document.getElementById("error").innerHTML = "' . htmlspecialchars("Couldn\'t record details. Please try again later") . '"; document.getElementById("error-message").style.display = "block";</script>';
					}
				}
			}
		}
	}
?>