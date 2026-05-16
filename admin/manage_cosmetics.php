<?php
include '../includes/admin_header.php'; // Separate header for admin
include '../includes/db_connect.php';

// Handle File Upload & Form Submission
if(isset($_POST['add_cosmetic'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target = "../assets/uploads/" . basename($image);

    $sql = "INSERT INTO cosmetics (name, description, image) VALUES ('$name', '$desc', '$image')";
    if(mysqli_query($conn, $sql)) {
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        echo "<script>alert('Product Added Successfully');</script>";
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM cosmetics WHERE id = $id");
    header("Location: manage_cosmetics.php");
}
?>

<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-4">
                <h4 class="brand-serif mb-4">Add New Extra</h4>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="small fw-bold">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Aromatherapy Candles">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Product Image</label>
                        <input type="file" name="image" class="form-control" required>
                    </div>
                    <button type="submit" name="add_cosmetic" class="btn btn-dark w-100">Save Product</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 p-4">
                <h4 class="brand-serif mb-4">Existing Inventory</h4>
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($conn, "SELECT * FROM cosmetics");
                        while($item = mysqli_fetch_assoc($res)):
                        ?>
                        <tr>
                            <td><img src="../assets/uploads/<?php echo $item['image']; ?>" width="50" class="rounded"></td>
                            <td><strong><?php echo $item['name']; ?></strong></td>
                            <td>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>