<?php include "db_connect.php"; ?>

<?php
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $profession = $_POST['profession'];
    $skill = $_POST['skill'];
    $experience = $_POST['experience'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];

    $sql = "INSERT INTO workers(name, profession, skill, experience, location, phone)
            VALUES('$name','$profession','$skill','$experience','$location','$phone')";

    mysqli_query($conn, $sql);

    header("Location: workers.php");
}
?>

<h2>Add Worker</h2>

<form method="POST">
    Name: <input type="text" name="name"><br><br>
    Profession: <input type="text" name="profession"><br><br>
    Skill: <input type="text" name="skill"><br><br>
    Experience: <input type="number" name="experience"><br><br>
    Location: <input type="text" name="location"><br><br>
    Phone: <input type="text" name="phone"><br><br>

    <button type="submit" name="submit">Add Worker</button>
</form>