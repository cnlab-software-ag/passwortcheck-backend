<?php
// configuration options ------------------------------------------------------------------------------------------------------------------

  // new results get only persisted if last entry of same IP is at least this old
  $MIN_AGE_IN_SECS = 5;

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

function cleanupOldActivities($db) {
  $query = "delete from activities where timestamp < date_sub(now(), interval 1 day);";
  $db->query($query);
}

function canWriteResult($db, $ip) {
  global $MIN_AGE_IN_SECS;
  $query = "select count(*) as count from activities where ip = '$ip' and timestamp > date_sub(now(), interval $MIN_AGE_IN_SECS SECOND);";
  $res = $db->query($query);
  if ($res) {
    return $res->fetch_assoc()['count'] == 0;
  } else {
    return 1;
  }
}

function updateActivityTable($db, $ip) {
  $query = "insert into activities (ip) values ('$ip');";
  $res = $db->query($query);
  return $res === TRUE;
}

function storeResult($db, $result) {
  $query  = "insert into results (result) values ($result);";
  $res = $db->query($query);
  return $res === TRUE;
}


if (!isset($_POST["result"])) {
  // bad request
  http_response_code(400);
  exit();
}
$res_str = $_POST["result"];

if ($res_str == "1") {
  $result = 1;
} else if ($res_str == "0") {
  $result = 0;
} else {
  // bad request
  http_response_code(400);
  exit();
}

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$ip = $_SERVER['REMOTE_ADDR'];
if (!canWriteResult($db, $ip)) {
  // too many requests
  http_response_code(429);
} else {
  if (!updateActivityTable($db, $ip)) {
    // server error
    http_repsonse_code(500);
  } else {
    if (storeResult($db, $result)) {
      // ok, no result
      http_response_code(201);
    } else {
      // server error
      http_response_code(500);
    }
  }
  cleanupOldActivities($db);
}

$db->close();

?>
