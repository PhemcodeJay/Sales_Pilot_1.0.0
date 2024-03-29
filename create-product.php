<?php
session_start(); // Start session

// Check if the 'Username' key exists in the session
if (isset($_SESSION['Username'])) {
    $username = $_SESSION['Username'];
} else {
    // If 'Username' is not set, redirect to the login page
    header('Location: loginpage.php');
    exit(); // Terminate script execution
}

// Include the database connection settings
require_once('config.php');

// Function to insert or update a category
function insertOrUpdateCategory($connection, $categoryName)
{
    // Prepare the SQL statement for insertion or update
    $sql = "INSERT INTO category (categoryname) VALUES (?) ON DUPLICATE KEY UPDATE categoryname = VALUES(categoryname)";
    $stmtCategory = $connection->prepare($sql);

    // Check if the statement was prepared successfully
    if (!$stmtCategory) {
        // Handle the database error here
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $connection->error]);
        exit();
    }

    // Bind the category name to the statement
    $stmtCategory->bind_param("s", $categoryName);

    // Execute the statement
    if (!$stmtCategory->execute()) {
        // Handle the database error here
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to insert or update the category: ' . $stmtCategory->error]);
        exit();
    }

    // Get the ID of the inserted/updated category
    $idCategories = $stmtCategory->insert_id;

    // Close the prepared statement
    $stmtCategory->close();

    return $idCategories;
}

// Function to insert product

function insertProduct($connection, $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories)
{
    // Prepare and bind the statement for the product
    $stmtProduct = $connection->prepare("INSERT INTO inventory (id_user, Username, Product, Description, Author, SupplyQty, StockQty, CostPrice, SalesPrice, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtProduct->bind_param("issssddddi", $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories);

    // Execute the statement and handle errors
    if ($stmtProduct->execute()) {
        // The insertion was successful
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['success' => 'Product inserted successfully.']);
    } else {
        // Handle the error here
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to execute the product statement: ' . $stmtProduct->error]);
    }
}

// Main logic to handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a mysqli connection and check the connection
    include('config.php');

    try {
        $connection = new mysqli($hostname, $username, $password, $database);

        if ($connection->connect_error) {
            throw new Exception("Error: " . $connection->connect_error);
        }
    } catch (Exception $e) {
        exit($e->getMessage());
    }

    function getUserID($Username, $connection) {
        // Prepare and execute a query to fetch the user ID based on the username
        $stmt = $connection->prepare("SELECT id_business FROM business_records WHERE Username = ?");
        $stmt->bind_param("s", $Username);
    
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['id_business'];
            } else {
                // Handle the case where the user is not found
                return null;
            }
        } else {
            // Handle the error if the query fails
            // You can log the error or handle it as needed
            return null;
        }
    }
    

    // Replace this with code to fetch the user ID based on the user's data or authentication
    $userId = getUserID($_SESSION['Username'], $connection);

    // Retrieve form data and sanitize it
    $formData = $_POST;

    // Define the sanitize function
    function sanitize($input)
    {
        return trim(strip_tags($input));
    }

    // Retrieve form data and sanitize it
    $Username = isset($formData['Username']) ? sanitize($formData['Username']) : '';
    $product = isset($formData['Product']) ? sanitize($formData['Product']) : '';
    $description = isset($formData['Description']) ? sanitize($formData['Description']) : '';
    $author = isset($formData['Author']) ? sanitize($formData['Author']) : '';
    $supplyQty = isset($formData['SupplyQty']) ? (int)$formData['SupplyQty'] : 0;
    $stockQty = isset($formData['StockQty']) ? (int)$formData['StockQty'] : 0;
    $costPrice = isset($formData['CostPrice']) ? (float)$formData['CostPrice'] : 0.0;
    $salesPrice = isset($formData['SalesPrice']) ? (float)$formData['SalesPrice'] : 0.0;
    $categoryName = isset($formData['category']) ? sanitize($formData['category']) : '';

    // Insert or update category
    $idCategories = insertOrUpdateCategory($connection, $categoryName);

    // Insert the product
    insertProduct($connection, $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories);

    // Redirect to the product page after the operation
    header('Location: http://localhost/WEB/product.php');
    exit();
}

// Close the prepared statement and database connection
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="http://localhost/WEB/assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="http://localhost/WEB/assets/img/favicon.png">
  <title>
    Sales Pilot - Create Product
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="http://localhost/WEB/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="http://localhost/WEB/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="http://localhost/WEB/assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <link href="http://localhost/WEB/newlogo.png" rel="icon">
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
  <style>
    /* Sales price */
#SalesPrice{
 background-color:#ceb9b9;
}

/* Cost price */
#CostPrice{
 background-color:#ceb9b9;
}

/* Stock */
#StockQty{
 background-color:#ceb9b9;
}

/* Supply */
#SupplyQty{
 background-color:#ceb9b9;
}

/* Author */
#Author{
 background-color:#ceb9b9;
}

/* Description */
#Description{
 background-color:#ceb9b9;
}

/* Category */
#category{
 background-color:#ceb9b9;
}

/* Username */
#Username{
 background-color:#ceb9b9;
}

/* Product */
#Product{
 background-color:#ceb9b9;
}

/* Import Google Fonts */
@import url("//fonts.googleapis.com/css2?family=Righteous:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap");

/* Button */
.card-body form .btn-primary{
 transform:translatex(83px) translatey(7px);
}

/* Card */
.main-content .page-header .my-auto .row .mx-auto .card{
 width:112% !important;
}

/* Label */
.card-body form label{
 color:#0c0c0d;
 font-weight:600;
 font-size:16px;
 font-family:'Righteous', display;
 text-align:left;
}

/* Import Google Fonts */
@import url("//fonts.googleapis.com/css2?family=Righteous:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap");

/* Category */
#category{
 font-size:20px;
 text-align:center;
 font-family:'Righteous', display;
}

</style>
</head>

<body class="bg-gray-200">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
       
      </div>
    </div>
  </div>
  <main class="main-content  mt-0">
    <div class="page-header align-items-start min-vh-100" style="background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1950&q=80');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-4 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Add New Product</h4>
                  <div class="row mt-3">
                  </div>
                </div>
              </div>
              <div class="card-body">
                <form method="post" action="create-product.php">
                    <div class="form-group">
                      <label for="Product">Product Name</label>
                      <input type="text" class="form-control" id="Product" name="Product" required>
                    </div>
              
                    <div class="form-group">
                      <label for="Username">Username</label>
                      <input type="text" class="form-control" id="Username" name="Username" required>
                    </div>
              
                    <div class="form-group">
                      <label for="category">Category</label>
                      <select class="form-control" id="category" name="category" required>
                        
                        <option value="">Select Category</option>
                        <option value="1">Tech</option>
                        <option value="2">Fashion</option>
                        <option value="3">Food</option>
                        <option value="4">Home</option>
                        <option value="5">Health</option>
                        <option value="6">Automotives</option>
                        <option value="7">Sports</option>
                        <option value="8">Kids</option>
                        <option value="9">Other</option>
                        <option value="10">Office</option>
                        <!-- Add more categories here -->
                      </select>
                    </div>
              
                    <div class="form-group">
                      <label for="Description">Description</label>
                      <textarea class="form-control" id="Description" name="Description" rows="2" required></textarea>
                    </div>
              
                    <div class="form-group">
                      <label for="Author">Author</label>
                      <input type="text" class="form-control" id="Author" name="Author" required>
                    </div>
              
                    <div class="form-group">
                      <label for="SupplyQty">Supply Qty</label>
                      <input type="number" class="form-control" id="SupplyQty" name="SupplyQty" required>
                    </div>
              
                    <div class="form-group">
                      <label for="StockQty">Stock Qty</label>
                      <input type="number" class="form-control" id="StockQty" name="StockQty" required>
                    </div>
              
                    <div class="form-group">
                      <label for="CostPrice">Cost Price ($)</label>
                      <input type="number" class="form-control" id="CostPrice" name="CostPrice" required>
                    </div>
              
                    <div class="form-group">
                      <label for="SalesPrice">Sales Price ($)</label>
                      <input type="number" class="form-control" id="SalesPrice" name="SalesPrice" required>
                    </div>
              
                    <button type="submit" class="btn btn-primary btn-block">Add Product</button>
                  </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <footer class="footer position-absolute bottom-2 py-2 w-100">
        <div class="container">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-12 col-md-6 my-auto">
              <div class="copyright text-center text-sm text-white text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
                made with <i class="fa fa-heart" aria-hidden="true"></i> by
                <a href="http://localhost/WEB/index.html" class="font-weight-bold text-white" target="_blank">Phemcode</a>
                for a better web.
              </div>
            </div>
            <div class="col-12 col-md-6">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="http://localhost/WEB/index.html" class="nav-link text-white" target="_blank">Phemcode</a>
                </li>
                <li class="nav-item">
                  <a href="http://localhost/WEB/index.html" class="nav-link text-white" target="_blank">About Us</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>
  <!--   Core JS Files   -->
  <script src="http://localhost/WEB/assets/js/core/popper.min.js"></script>
  <script src="http://localhost/WEB/assets/js/core/bootstrap.min.js"></script>
  <script src="http://localhost/WEB/assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="http://localhost/WEB/assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
 
</body>

</html>