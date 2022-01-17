<?php
// configuration options ------------------------------------------------------------------------------------------------------------------

  // allowed origins
  $allowedOrigins = getenv("ALLOWED_ORIGINS");

  // DB related settings
  $db_host = getenv("DB_HOST");
  $db_name = getenv("DB_NAME");
  $db_user = getenv("DB_USERNAME");
  $db_pass = getenv("DB_PASSWORD");

  // number of days
  $num_days = 366;

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

$query  = "select A.date, B.count as weak, C.count as strong from (";
$query .= "  select a.date from ( ";
$query .= "    select date_format(now() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY, '%Y-%m-%d') as date from ( ";
$query .= "      select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all ";
$query .= "         select 6 union all select 7 union all select 8 union all select 9) as a ";
$query .= "      cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all ";
$query .= "         select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b ";
$query .= "      cross join (select 0 as a union all select 1 union all select 2 union all select 3) as c ";
$query .= "    ) a ";
$query .= "    where a.date between now() - INTERVAL ${num_days} DAY and now() ";
$query .= "    order by date ";
$query .= ") A ";
$query .= "left join ( ";
$query .= "  select date_format(timestamp, '%Y-%m-%d') as date, count(*) as count from results where result = 0 group by date ";
$query .= ") B ";
$query .= "on A.date = B.date ";
$query .= "left join ( ";
$query .= "  select date_format(timestamp, '%Y-%m-%d') as date, count(*) as count from results where result = 1 group by date ";
$query .= ") C ";
$query .= "on A.date = C.date ";
$query .= "order by date;";

$res = $db->query($query);
if ($res->num_rows > 0) {
  foreach($res as $row) {
    $date = $row['date'];
    $weak = intval($row['weak']);
    $strong = intval($row['strong']);
    $checks["checks"][$date] = Array();
    $checks["checks"][$date]["id"] = $date;
    $checks["checks"][$date]["data"] = [$weak, $strong];
    $checks["checks"][$date]["checkTotal"] = $weak + $strong;
  }
}

$db->close();

print(json_encode($checks));

?>

