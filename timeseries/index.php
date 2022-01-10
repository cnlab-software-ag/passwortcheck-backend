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

for($i=0;$i<365;$i++) {
  $query = "select date_format(date_sub(now(), interval $i day), '%Y-%m-%d') as date from dual";
  $res = $db->query($query);
  $date = $res->fetch_assoc()['date'];

  $checks["checks"][$date] = Array();
  $checks["checks"][$date]["id"] = $date;

  $query = "select result, count(*) as num from results where timestamp >= '$date 00:00:00' and timestamp <= '$date 23:59:59' group by result";
  $res = $db->query($query);
  $total = 0;
  $data = [0, 0];
  if ($res->num_rows > 0) {
    foreach($res as $row) {
      $total += $row['num'];
      $val = intval($row['result']);
      $data[$val] = intval($row['num']);
    }
  }
  $checks["checks"][$date]["data"] = $data;
  $checks["checks"][$date]["checkTotal"] = intval($total);
}

$db->close();

print(json_encode($checks));

?>

