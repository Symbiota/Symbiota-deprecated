<?php
  // Get request vars
  $argName = $_GET["name"] || "";
  $argSunlight = $_GET["sunlight"] || "any";
  $argMoisture = $_GET["moisture"] || "any";

  // Validate request vars
  if (!in_array($argSunlight, ["any", "sun", "part-shade", "full-shade"])) {
    $argSunlight = "any";
  }

  if (!in_array($argMoisture, ["any", "wet", "moist", "dry"])) {
    $argMoisture = "any";
  }

  header("Content-Type", "application/json; charset=utf-8");
  echo "[]";
?>
