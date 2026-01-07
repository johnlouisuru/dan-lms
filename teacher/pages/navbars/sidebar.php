<?php
// Get the full request URI
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the URI to separate path and query
$parsedUrl = parse_url($requestUri);

// Get only the path part (no query string)
$pathOnly = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

// Remove leading/trailing slashes
$trimmed = trim($pathOnly, "/");

// Split into segments
$segments = explode("/", $trimmed);

// Get the last segment (e.g. "dashboard")
$currentPath = ucfirst(end($segments));

// Collect GET parameters if available
$getHolder = !empty($_GET) ? $_GET : [];

// // Debug (optional)
// echo "Current Path: " . htmlspecialchars($currentPath) . "<br>";
// echo "GET Holder: <pre>" . print_r($getHolder, true) . "</pre>";
?>
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2  bg-white my-2" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand px-4 py-3 m-0" href=" https://demos.creative-tim.com/material-dashboard/pages/dashboard " target="_blank">
        <img src="../<?= $_ENV['PAGE_ICON'] ?>" class="navbar-brand-img" width="26" height="26" alt="main_logo">
        <span class="ms-1 text-sm text-dark"><?=$_ENV['PAGE_HEADER']?> </span>
      </a>
    </div>
    <hr class="horizontal dark mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?= navActive($currentPath, 'Dashboard') ?>" href="dashboard">
            <i class="material-symbols-rounded opacity-5">dashboard</i>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        
        <li class="nav-item mt-3">
          <!-- <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Account pages</h6> -->
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link text-dark" href="../pages/sign-in.html">
            <i class="material-symbols-rounded opacity-5">login</i>
            <span class="nav-link-text ms-1">Sign In</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="../pages/sign-up.html">
            <i class="material-symbols-rounded opacity-5">assignment</i>
            <span class="nav-link-text ms-1">Sign Up</span>
          </a>
        </li> -->
        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-dark font-weight-bolder opacity-5">Data  Management</h6>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= navActive($currentPath, 'Students') ?>" href="students">
            <i class="material-symbols-rounded opacity-5">person</i>
            <span class="nav-link-text ms-1">Students</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= navActive($currentPath, 'Teachers') ?>" href="teachers">
            <i class="material-symbols-rounded opacity-5">person</i>
            <span class="nav-link-text ms-1">Teachers</span>
          </a>
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link <?= navActive($currentPath, 'Sections') ?>" href="sections">
            <i class="material-symbols-rounded opacity-5">files</i>
            <span class="nav-link-text ms-1">All Sections</span>
          </a>
        </li> -->
        <!-- <li class="nav-item">
          <a class="nav-link <?= navActive($currentPath, 'Stages') ?>" href="stages">
            <i class="material-symbols-rounded opacity-5">files</i>
            <span class="nav-link-text ms-1">All Stages</span>
          </a>
        </li> -->
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">
    <div class="mx-3">
        <!-- <a class="btn btn-outline-dark mt-4 w-100" href="https://www.creative-tim.com/learning-lab/bootstrap/overview/material-dashboard?ref=sidebarfree" type="button">Documentation</a> -->
        <a class="btn bg-gradient-info w-100" href="../classroom" type="button">Back to Classroom</a>
      </div>
      <div class="mx-3">
      
        <!-- <a class="btn btn-outline-dark mt-4 w-100" href="https://www.creative-tim.com/learning-lab/bootstrap/overview/material-dashboard?ref=sidebarfree" type="button">Documentation</a> -->
        <a class="btn bg-gradient-dark w-100" href="../logout/" type="button"><i class="bi bi-box-arrow-right text text-danger">Logout</i> </a>
      </div>
    </div>
  </aside>