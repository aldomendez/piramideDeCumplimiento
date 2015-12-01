<?php
require '../Slim/Slim.php';
include "../inc/database.php";


/*
La aplicacion servira para poner los datos de la piramide en una tabla de sqlite,
los datos los sacara de la base de datos haciendo querys para obtener los datos
de outs del dia con la lista de codigos que se carguen a la aplicacion

Lo que deberia de tener la aplicacion es

* Una tabla con las areas que se quieren estar monitoreando, el target
* Los codigos de cada area
* y otra con los datos por dia de los outs, y la meta actual.

Lo que todavia no tengo idea es como se van a manejar las metas de la semana o
o del mes. Hay muchas cosas que tengo que asumir y no tengo la información
suficiente para asumir todo lo que se va a hacer.


## Querys en sqlite basados en tiempo

### Para obtener los datos de la semana
Utilizando el siguiente query, obtnemos los datos de la semana en curso:

```
select * from history where strftime('%W',date) = strftime('%W','now');
```

Esta parte del query regresa la semana en la que estamos

```
select strftime('%W','now');
```

### Para obtener los targets del dia en curso

```
select target from targets where kpiid = 1 and day_of_week = strftime('%w','now');
```


### Para obtener el historial del mes

```
select * from history where strftime('%Y%m',date) = strftime('%Y%m','now');
```

para el mes pasado:

```
select * from history where strftime('%Y%m',date) = strftime('%Y%m','now','-1 month');
```

En esta parte creo que es importante que el formato de la fecha incluya el año, para que
no me valla a tomar datos de años pasados que correspondan al mismo mes
*/

date_default_timezone_set('America/Matamoros');

$app = new Slim();
$app->sqlite = new PDO('sqlite:piramide.sqlitedb');
$app->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);




$app->get('/', 'index' );
$app->get('/update_outs/:kpiid', 'retrieve_from_osfm_and_put_in_sqlite' );
$app->get('/piramid/:kpiid', 'build_and_return_piramid_data');
$app->get('/list','list_available_piramids_data');
$app->get('/init_tables', 'confirmTableCreation' );


function index()
{
    echo "<h1>Error: solo puedes accesar el servicio con una peticion. Revisa la API!</h1>";
}


function retrieve_from_osfm_and_put_in_sqlite($kpiid){
  global $app;
  try {
      $date = 'now';
      save_daily_outs($kpiid, $date, getDataFromOSFM($kpiid), getDaylyTarget($kpiid));
  } catch (Exception $e) {
    echo ('Caught exception: \n'.  print_r($e). "\n");
  }
}

function getDataFromOSFM($kpiid)
{
  // return '20';
  try {
    $results = null;
    $DB = new MxOptix();
    $query = "SELECT Count(job) qty FROM apps.xxbi_cyp_activity_log_v@osfm
    WHERE ORGANIZATION_CODE = 'F07'AND item IN ({codes})
    AND systemdate_est BETWEEN To_Date(To_Char(SYSDATE-2,'yyyymmdd')||'0730','yyyymmddhh24mi') AND
    To_Date(To_Char(SYSDATE-1,'yyyymmdd')||'0730','yyyymmddhh24mi')AND OPERATION_TYPE = 'DONE'
    ";
    $query = str_replace("{codes}", getCodes($kpiid) , $query);
    $DB->setQuery($query);
    oci_execute($DB->statement);
    oci_fetch_all($DB->statement, $results,0,-1,OCI_FETCHSTATEMENT_BY_ROW);
    $DB->close();
    return $results[0]['QTY'];
  } catch (Exception $e) {
    $DB->close();
    echo ('Caught exception: \n'.  print_r($e). "\n");
  }
}

function flat_array($array)
{
  return "'" . implode("','", $array) . "'";
}

function getCodes($kpiid)
{
  global $app;
  $stmt = $app->sqlite->query("select code from codes where kpiid = " . $kpiid);
  $codes = array();
  foreach ($stmt as $row) {
    array_push($codes, $row['code']);
  }
  return flat_array($codes);
}

function getDaylyTarget($kpiid)
{
  global $app;
  $stmt = $app->sqlite->query("select target from targets where kpiid = " . $kpiid . " and day_of_week = strftime('%w','now')");
  $ansArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $ansArray[0]['target'];
}

function save_daily_outs($kpiid, $date = 'now', $actual, $target){
  global $app;
  $app->sqlite->exec("insert into history ('kpiid', 'date', 'actual', 'target') values (" . $kpiid . ", date('" . $date . "'), '" . $actual . "', '" . $target . "')");
}

function list_available_piramids_data()
{
  global $app;
  try {
    $res = $app->sqlite->query('select * from kpi')->fetchAll(PDO::FETCH_ASSOC);
    echo(json_encode($res));
  } catch (Exception $e) {
    echo $e->message;
  }
}

function build_and_return_piramid_data($kpiid)
{
  echo "<pre>";
  global $app;
  try {
    $ans = array();
    $ans['kpi_info'] = $app->sqlite->query('select * from kpi where id =' . $kpiid)->fetchAll(PDO::FETCH_ASSOC);
    $ans['target_day'] = $app->sqlite->query('select target from targets where kpiid = '. $kpiid ." and day_of_week = strftime('%w','now')")->fetchAll(PDO::FETCH_ASSOC)[0]['target'];
    $ans['target_day'] = $app->sqlite->query('select target from targets where kpiid = '. $kpiid ." and day_of_week = strftime('%w','now')")->fetchAll(PDO::FETCH_ASSOC)[0]['target'];
    $ans['history'] = $app->sqlite->query("select * from history where strftime('%Y%m',date) = strftime('%Y%m','now') and kpiid = " . $kpiid )->fetchAll(PDO::FETCH_ASSOC);
    print_r($ans);
  } catch (Exception $e) {
    
  }
  echo "</pre>";
}

function sqlite3_example()
{
  try
  {
    //open the database
    $db = new PDO('sqlite:history.pyramid.sqlite');

    //create the database
    $db->exec("CREATE TABLE Dogs (Id INTEGER PRIMARY KEY, Breed TEXT, Name TEXT, Age INTEGER)");

    //insert some data...
    $db->exec("INSERT INTO Dogs (Breed, Name, Age) VALUES ('Labrador', 'Tank', 2);".
               "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Husky', 'Glacier', 7); " .
               "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Golden-Doodle', 'Ellie', 4);");

    //now output the data to a simple html table...
    print "<table border=1>";
    print "<tr><td>Id</td><td>Breed</td><td>Name</td><td>Age</td></tr>";
    $res = $db->query('SELECT * FROM Dogs');
    foreach($res as $row)
    {
      print "<tr><td>".$row['Id']."</td>";
      print "<td>".$row['Breed']."</td>";
      print "<td>".$row['Name']."</td>";
      print "<td>".$row['Age']."</td></tr>";
    print "</table>";
  }
    // close the database connection
    $db = NULL;
  }
  catch(PDOException $e)
  {
    print 'Exception : '.$e->getMessage();
  }

}














function array2csv($array) {
    $ans = '';
    $start = true;
    $head = array();
    foreach($array as $key => $value) {
        if ($start) {
            foreach ($value as $key2 => $value2) {
                array_push($head, $key2);
            }
            $ans .= implode(",", $head) . PHP_EOL;
            $start = false;
        }
        $ans .= implode(',', $value) . PHP_EOL;
    }
    return $ans;
}

$app->run();




function init_tables(){

/*
* Una tabla con las areas que se quieren estar monitoreando, el target
* Los codigos de cada area
* y otra con los datos por dia de los outs, y la meta actual.
*/
  $db = new PDO('sqlite:history.pyramid.sqlite');
  $db->exec("PRAGMA foreign_keys = ON");
  $db->exec("drop table if exists areas");
  $db->exec("drop table if exists codigos");
  $db->exec("drop table if exists outs");
  $db->exec("CREATE TABLE Areas (
    Id INTEGER PRIMARY KEY,
    out_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    name TEXT,
    target INTEGER);");
  $db->exec("CREATE TABLE codigos (
    Id INTEGER PRIMARY KEY,
    out_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    name TEXT,
    target INTEGER);");
  $db->exec("CREATE TABLE outs (
    Id INTEGER PRIMARY KEY,
    out_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    name TEXT,
    target INTEGER);");
}





/*

Inicializadores para la base de datos:

Ejecutada esta secuencia se crea la base de datos requerida para
que funcione la aplicación.




drop table if exists target_history;
drop table if exists history;
drop table if exists codes;
drop table if exists targets;
drop table if exists status;
drop table if exists kpi;
PRAGMA foreign_keys = ON;

CREATE TABLE 'kpi' (
    'name' TEXT NOT NULL,
    'color' TEXT NOT NULL DEFAULT ('#FFFFFF'),
    'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    'area' TEXT NOT NULL,
    'type' text not null default ('OUTS'), 
    'title_offset_x' TEXT DEFAULT ('0'),
    'title_offset_y' TEXT DEFAULT ('0')
);
CREATE TABLE 'target_history' (
    'kpiid' INTEGER NOT NULL,
    'state' TEXT NOT NULL,
    'user' TEXT NOT NULL,
    'update_date' TEXT NOT NULL,
    'day_of_week' TEXT NOT NULL,
    'target' INTEGER NOT NULL DEFAULT (0),
    FOREIGN KEY(kpiid) REFERENCES kpi(id)
);
CREATE TABLE 'history' (
    'kpiid' INTEGER NOT NULL,
    'date' TEXT NOT NULL,
    'actual' INTEGER NOT NULL DEFAULT (0),
    'target' INTEGER NOT NULL,
    'is_holiday' integer not null default (0),
    FOREIGN KEY(kpiid) REFERENCES kpi(id)
);
CREATE TABLE 'codes' (
    'kpiid' INTEGER NOT NULL,
    'code' TEXT NOT NULL,
    FOREIGN KEY(kpiid) REFERENCES kpi(id) on update cascade on delete cascade
);
CREATE TABLE 'targets' (
    'kpiid' INTEGER NOT NULL,
    'day_of_week' TEXT NOT NULL,
    'last_update_user' TEXT NOT NULL,
    'target' INTEGER NOT NULL DEFAULT (0),
    FOREIGN KEY(kpiid) REFERENCES kpi(id)  on update cascade on delete cascade
);
CREATE TABLE status (
    'kpiid' TEXT NOT NULL,
    'qty' INTEGER NOT NULL DEFAULT (0),
    'datetime' TEXT NOT NULL,
    FOREIGN KEY(kpiid) REFERENCES kpi(id) on update cascade on delete cascade
);

CREATE INDEX 'codes_kpiid' on codes (kpiid ASC);
CREATE INDEX 'history_kpiid' on history (kpiid ASC);
CREATE INDEX 'kpi_id' on kpi (id ASC);
CREATE INDEX 'target_h_kpiid' on target_history (kpiid ASC);
CREATE INDEX 'target_h_state' on target_history (state ASC);
CREATE INDEX 'target_h_id_day_of_week' on target_history (kpiid ASC, day_of_week ASC);
CREATE INDEX 'history_id_date' on history (kpiid ASC, date ASC);
CREATE INDEX 'targets_id' on targets (kpiid ASC);
CREATE TRIGGER 'Init_Target'
   AFTER INSERT ON kpi
BEGIN
    insert into targets
    (kpiid, day_of_week, target, last_update_user)
              select new.id, 0, 0, 'user'
    union all select new.id, 1, 0, 'user'
    union all select new.id, 2, 0, 'user'
    union all select new.id, 3, 0, 'user'
    union all select new.id, 4, 0, 'user'
    union all select new.id, 5, 0, 'user'
    union all select new.id, 6, 0, 'user';
END;
CREATE TRIGGER 'keep_history'
   AFTER UPDATE ON targets
BEGIN
    update target_history set state = 'O'
    where kpiid = new.kpiid and day_of_week = new.day_of_week;
    insert into target_history
    (kpiid, state, user, update_date, day_of_week, target)
  select new.kpiid, 'C', 'user', datetime('now'),new.day_of_week, new.target;
END;
CREATE TRIGGER 'i_keep_history'
   AFTER insert ON targets
BEGIN
    insert into target_history
    (kpiid, state, user, update_date, day_of_week, target)
  select new.kpiid, 'C', 'user', datetime('now'),new.day_of_week, new.target;
END;



BEGIN TRANSACTION;
insert into kpi ("name", "color", "id", "Area", "type", "title_offset_x", "title_offset_y") values ('OUTS', '#FFFFFF', '1', 'PMQPSK', 'OUTS', '0', '0');
insert into kpi ("name", "color", "id", "Area", "type", "title_offset_x", "title_offset_y") values ('OUTS', '#FFFF00', '2', 'µITLA', 'OUTS', '0', '0');


insert into codes ('kpiid', 'code') values ('1', 'RX-PMQPSK-100-B3');
insert into codes ('kpiid', 'code') values ('1', 'RX-PMQPSK-100-H1');
insert into codes ('kpiid', 'code') values ('1', 'RX-PMQPSK-100-JV2');
insert into codes ('kpiid', 'code') values ('1', 'RX-PMQPSK-40-D1');


insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-1 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-2 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-3 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-4 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-5 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-6 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-7 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-8 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-9 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-10 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-11 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-12 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-13 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-14 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-16 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-17 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-18 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-19 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-21 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-22 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-23 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-24 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-25 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-26 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-27 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-28 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-29 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-30 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-32 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-33 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-34 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-35 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-36 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-37 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-38 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-39 days'), '20', '21', '0');
insert into history ("kpiid", "date", "actual", "target", "is_holiday") values ('1', date('now','-40 days'), '20', '21', '0');
COMMIT;

*/
