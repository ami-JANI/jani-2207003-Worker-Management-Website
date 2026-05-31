<?php include "db_connect.php"; ?>

<?php
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM workers WHERE id=$id");
$row = mysqli_fetch_assoc($result);
?>

<h2>Worker Details</h2>

<p><b>Name:</b> <?= $row['name'] ?></p>
<p><b>Profession:</b> <?= $row['profession'] ?></p>
<p><b>Skill:</b> <?= $row['skill'] ?></p>
<p><b>Experience:</b> <?= $row['experience'] ?> years</p>
<p><b>Location:</b> <?= $row['location'] ?></p>
<p><b>Phone:</b> <?= $row['phone'] ?></p>

<br>
<a href="workers.php">Back</a>