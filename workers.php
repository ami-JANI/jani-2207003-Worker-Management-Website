<?php include "db_connect.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Workers List</title>
</head>
<body>

<h2>All Workers</h2>

<a href="add_worker.php">+ Add Worker</a>
<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Profession</th>
        <th>Experience</th>
        <th>Location</th>
        <th>Actions</th>
    </tr>

<?php
$result = mysqli_query($conn, "SELECT * FROM workers");

while ($row = mysqli_fetch_assoc($result)) {
?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['profession'] ?></td>
        <td><?= $row['experience'] ?> years</td>
        <td><?= $row['location'] ?></td>
        <td>
            <a href="worker_details.php?id=<?= $row['id'] ?>">View</a> |
            <a href="edit_worker.php?id=<?= $row['id'] ?>">Edit</a> |
            <a href="delete_worker.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this worker?')">Delete</a>
        </td>
    </tr>
<?php } ?>

</table>

</body>
</html>