<?php
// configuration options ------------------------------------------------------------------------------------------------------------------

  // allowed origins
  $allowedOrigins = getenv("ALLOWED_ORIGINS");

  // DB related settings
  $db_host = getenv("DB_HOST");
  $db_name = getenv("DB_NAME");
  $db_user = getenv("DB_USERNAME");
  $db_pass = getenv("DB_PASSWORD");

// no changes below -----------------------------------------------------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
if (in_array($_SERVER["HTTP_ORIGIN"], explode(",", $allowedOrigins))) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
} else {
  // unauthorized
  http_response_code(401);
  exit();
}

$checks = Array("checks" => Array());
$total = 0;
$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$intervals = Array(
  "last24Hours" => "1 day",
  "last7Days" => "7 day",
  "last30Days" => "30 day",
  "last12Months" => "1 year",
);

foreach($intervals as $k => $v) {

  $checks["checks"][$k] = Array(
    "id" => "$k",
    "checkTotal" => 0,
    "data" => [0, 0]
  );

  $query = "select result, count(*) as num from results where timestamp >= (now() - interval $v) group by result";
  $total = 0;
  $res = $db->query($query);
  $data = [0, 0];
  if ($res->num_rows > 0) {
    foreach($res as $row) {
      $total += $row['num'];
      $val = intval($row['result']);
      $data[$val] = intval($row['num']);
    }
    $checks["checks"][$k]["data"] = $data;
    $checks["checks"][$k]["checkTotal"] = intval($total);
  }
}

$db->close();

print(json_encode($checks));

?>

