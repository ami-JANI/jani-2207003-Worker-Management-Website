<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    font-family: Arial;
    background:#f4f6f9;
}

/* Top Navbar */
.navbar{
    background:#1f2937;
    color:white;
    padding:15px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.navbar h2{
    margin:0;
    font-size:20px;
}

.nav-links a{
    color:white;
    margin:0 10px;
    text-decoration:none;
    font-size:14px;
}

/* Layout */
.container{
    display:flex;
    padding:20px;
}

/* Worker Grid */
.workers{
    flex:3;
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:15px;
}

/* Worker Card */
.card{
    background:white;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.card h3{
    margin:0;
    font-size:16px;
}

.card p{
    font-size:13px;
    color:#555;
}

/* Filter Panel */
.filter{
    flex:1;
    background:white;
    padding:15px;
    margin-left:20px;
    border-radius:10px;
    height:fit-content;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.filter h3{
    margin-top:0;
}

select, input{
    width:100%;
    padding:8px;
    margin-bottom:10px;
}

button{
    width:100%;
    padding:10px;
    background:#1f2937;
    color:white;
    border:none;
    cursor:pointer;
    border-radius:5px;
}

button:hover{
    background:#111827;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <h2>WorkForce Manager</h2>
    <div class="nav-links">
        <a href="#">Browse Workers</a>
        <a href="add_worker.php">Add Worker</a>
        <a href="#">Bookings</a>
        <a href="#">Analytics</a>
    </div>
</div>

<!-- MAIN -->
<div class="container">

    <!-- WORKERS -->
    <div class="workers">

        <?php
        include "db_connect.php";

        $result = mysqli_query($conn, "SELECT * FROM workers");

        while($row = mysqli_fetch_assoc($result)){
        ?>

        <div class="card">
            <h3><?= $row['name'] ?></h3>
            <p><b>Profession:</b> <?= $row['profession'] ?></p>
            <p><b>Experience:</b> <?= $row['experience'] ?> yrs</p>
            <p><b>Location:</b> <?= $row['location'] ?></p>
            <p><b>Phone:</b> <?= $row['phone'] ?></p>
        </div>

        <?php } ?>

    </div>

    <!-- FILTER -->
    <div class="filter">
        <h3>Filter</h3>

        <label>Profession</label>
        <select>
            <option>All Professions</option>
        </select>

        <label>Location</label>
        <select>
            <option>All Locations</option>
        </select>

        <label>Min Experience (yrs)</label>
        <input type="number" placeholder="Any">

        <label>Min Rating</label>
        <input type="number" step="0.1" placeholder="Any">

        <label>Availability</label>
        <select>
            <option>Any</option>
        </select>

        <button>Apply Filter</button>
        <button style="background:#6b7280;margin-top:5px;">Reset</button>

        <br><br>
        <label>Sort By</label>
        <select>
            <option>Rating</option>
            <option>Experience</option>
            <option>Newest</option>
        </select>
    </div>

</div>

</body>
</html>