<?php include "db_connect.php"; ?>

<?php
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM workers WHERE id=$id");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $profession = $_POST['profession'];
    $skill = $_POST['skill'];
    $experience = $_POST['experience'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];

    $sql = "UPDATE workers SET 
            name='$name',
            profession='$profession',
            skill='$skill',
            experience='$experience',
            location='$location',
            phone='$phone'
            WHERE id=$id";

    mysqli_query($conn, $sql);

    header("Location: workers.php");
}
?>

<h2>Edit Worker</h2>

<form method="POST">
    Name: <input type="text" name="name" value="<?= $row['name'] ?>"><br><br>
    Profession: <input type="text" name="profession" value="<?= $row['profession'] ?>"><br><br>
    Skill: <input type="text" name="skill" value="<?= $row['skill'] ?>"><br><br>
    Experience: <input type="number" name="experience" value="<?= $row['experience'] ?>"><br><br>
    Location: <input type="text" name="location" value="<?= $row['location'] ?>"><br><br>
    Phone: <input type="text" name="phone" value="<?= $row['phone'] ?>"><br><br>

    <button type="submit" name="update">Update Worker</button>
</form>