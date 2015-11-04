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



*/

$app = new Slim();

$app->get('/', 'index' );
$app->get('/update_outs', 'retrieve_from_osfm_and_put_in_sqlite' );
$app->get('/init_tables', 'init_tables' );


function index()
{
    echo "<h1>Error: solo puedes accesar el servicio con una peticion. Revisa la API!</h1>";
}


function retrieve_from_osfm_and_put_in_sqlite(){
    try {
        
        $DB = new MxOptix();
        // global $app;
        // $body = $app->request()->getBody();
        // $body = json_decode($body, true);
        // print_r($body);
        $DB->setQuery("SELECT Count(job) qty FROM apps.xxbi_cyp_activity_log_v@osfm
          WHERE
          ORGANIZATION_CODE = 'F07'AND
          item IN (
          'RX-PMQPSK-100-B3',
          'RX-PMQPSK-100-H1',
          'RX-PMQPSK100-JV2',
          'RX-PMQPSK-40-D1')
          AND systemdate_est BETWEEN To_Date(To_Char(SYSDATE-2,'yyyymmdd')||'0730','yyyymmddhh24mi') AND 
          To_Date(To_Char(SYSDATE-1,'yyyymmdd')||'0730','yyyymmddhh24mi')AND OPERATION_TYPE = 'DONE'
        ");
        $results = null;
        oci_execute($DB->statement);
        oci_fetch_all($DB->statement, $results,0,-1,OCI_FETCHSTATEMENT_BY_ROW);
        print_r($results);
        save_daily_outs($results);
        $DB->close();
    } catch (Exception $e) {
        $DB->close();
        echo ('Caught exception: '.  $e->getMessage(). "\n");
    }
}

function save_daily_outs(){
  $db = new PDO('sqlite:history.pyramid.sqlite');
  $db->exec("drop table if exists dogs");    
  $db->exec("CREATE TABLE Dogs (
    Id INTEGER PRIMARY KEY,
    out_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Name TEXT,
    Age INTEGER)");    
  $db->exec("INSERT INTO Dogs (Name, Age) VALUES ( 'Tank', 2);".
             "INSERT INTO Dogs (Name, Age) VALUES ( 'Glacier', 7); " .
             "INSERT INTO Dogs (Name, Age) VALUES ('Ellie', 4);");
  $res = $db->query('SELECT * FROM Dogs');
  // print_r($res);
  echo "<pre>";
  foreach($res as $row)
  {
    print $row['Id'].PHP_EOL;
    print $row['out_date'].PHP_EOL;
    print $row['Name'].PHP_EOL;
    print $row['Age'].PHP_EOL;
  }
  echo "</pre>";
}

function init_tables(){

/*
* Una tabla con las areas que se quieren estar monitoreando, el target
* Los codigos de cada area
* y otra con los datos por dia de los outs, y la meta actual.
*/
  $db = new PDO('sqlite:history.pyramid.sqlite');
  $db->exec("drop table if exists areas");
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
  $db->exec("INSERT INTO Dogs (Name, Age) VALUES ( 'Tank', 2);".
             "INSERT INTO Dogs (Name, Age) VALUES ( 'Glacier', 7); " .
             "INSERT INTO Dogs (Name, Age) VALUES ('Ellie', 4);");
  $res = $db->query('SELECT * FROM Dogs');
  // print_r($res);
  echo "<pre>";
  foreach($res as $row)
  {
    print $row['Id'].PHP_EOL;
    print $row['out_date'].PHP_EOL;
    print $row['Name'].PHP_EOL;
    print $row['Age'].PHP_EOL;
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







/*

Inicializadores para la base de datos:

Ejecutada esta secuencia se crea la base de datos requerida para
que funcione la aplicación.

CREATE TABLE "kpi" (
    "name" TEXT NOT NULL,
    "color" TEXT NOT NULL DEFAULT ('#FFFFFF'),
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "Area" TEXT NOT NULL,
    "title_offset_x" TEXT DEFAULT ('0'),
    "title_offset_y" TEXT DEFAULT ('0'),
    "entry_date" TEXT default datetime('now')
)
;
CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE "codes" (
    "kpiid" INTEGER NOT NULL,
    "code" TEXT NOT NULL
);
CREATE TABLE "targets" (
    "kpiid" INTEGER NOT NULL,
    "mon" INTEGER NOT NULL DEFAULT (0),
    "tue" INTEGER NOT NULL DEFAULT (0),
    "wed" INTEGER NOT NULL DEFAULT (0),
    "thu" INTEGER NOT NULL DEFAULT (0),
    "fri" INTEGER NOT NULL DEFAULT (0),
    "sat" INTEGER NOT NULL DEFAULT (0),
    "sun" INTEGER NOT NULL DEFAULT (0)
);
CREATE TABLE "history" (
    "kpiid" INTEGER NOT NULL,
    "date" TEXT NOT NULL,
    "actual" INTEGER NOT NULL DEFAULT (0),
    "target" INTEGER NOT NULL
);
CREATE TABLE "main"."target_history" (
    "kpiid" INTEGER NOT NULL,
    "state" TEXT NOT NULL,
    "user" TEXT NOT NULL,
    "update_date" TEXT NOT NULL,
    "mon" TEXT NOT NULL,
    "tue" TEXT NOT NULL,
    "wed" TEXT NOT NULL,
    "thu" TEXT NOT NULL,
    "fri" TEXT NOT NULL,
    "sat" TEXT NOT NULL,
    "sun" TEXT NOT NULL
);

CREATE INDEX "ids" on codes (kpiid ASC)
CREATE INDEX "hist-kpi" on history (kpiid ASC)
CREATE INDEX "hist-date" on history (date ASC)
CREATE INDEX "kpi-id" on kpi (id ASC)
CREATE INDEX "target-kpiid" on targets (kpiid ASC)

CREATE TRIGGER IF NOT EXISTS 'keep_history'
   AFTER UPDATE ON kpi
BEGIN
    update target_history set state = 'O'
    where kpiid = new.kpiid;
    insert into target_history 
    (state,kpiid,user,update_date,mon,tue,wed,thu,fri,sat,sun)
    values(
    new.state,new.kpiid,new.user,datetime('now'),new.mon,
    new.tue,new.wed,new.thu,new.fri,new.sat,new.sun);   
END;



*/

/*
CREATE TABLE sqlite_sequence(name,seq)
CREATE TABLE "codes" (
    "kpiid" INTEGER NOT NULL,
    "code" TEXT NOT NULL
)
CREATE TABLE "targets" (
    "kpiid" INTEGER NOT NULL,
    "mon" INTEGER NOT NULL DEFAULT (0),
    "tue" INTEGER NOT NULL DEFAULT (0),
    "wed" INTEGER NOT NULL DEFAULT (0),
    "thu" INTEGER NOT NULL DEFAULT (0),
    "fri" INTEGER NOT NULL DEFAULT (0),
    "sat" INTEGER NOT NULL DEFAULT (0),
    "sun" INTEGER NOT NULL DEFAULT (0)
)
CREATE TABLE "history" (
    "kpiid" INTEGER NOT NULL,
    "date" TEXT NOT NULL,
    "actual" INTEGER NOT NULL DEFAULT (0),
    "target" INTEGER NOT NULL
)
CREATE INDEX "ids" on codes (kpiid ASC)
CREATE INDEX "hist-kpi" on history (kpiid ASC)
CREATE INDEX "hist-date" on history (date ASC)
CREATE INDEX "target-kpiid" on targets (kpiid ASC)
CREATE TABLE "kpi" (
    "name" TEXT NOT NULL,
    "color" TEXT NOT NULL DEFAULT ('#FFFFFF'),
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "Area" TEXT NOT NULL,
    "title_offset_x" TEXT DEFAULT ('0'),
    "title_offset_y" TEXT DEFAULT ('0')
)
CREATE INDEX "kpi-id" on kpi (id ASC)
CREATE TABLE "target_history" (
    "kpiid" INTEGER NOT NULL,
    "state" TEXT NOT NULL,
    "user" TEXT NOT NULL,
    "update_date" TEXT NOT NULL,
    "mon" TEXT NOT NULL,
    "tue" TEXT NOT NULL,
    "wed" TEXT NOT NULL,
    "thu" TEXT NOT NULL,
    "fri" TEXT NOT NULL,
    "sat" TEXT NOT NULL,
    "sun" TEXT NOT NULL
)
CREATE TRIGGER 'keep_history'
   AFTER UPDATE ON kpi
BEGIN
    update target_history set state = 'O'
    where kpiid = new.kpiid;
    insert into target_history 
    (state,kpiid,user,update_date,mon,tue,wed,thu,fri,sat,sun)
    values(
    new.state,new.kpiid,new.user,datetime('now'),new.mon,
    new.tue,new.wed,new.thu,new.fri,new.sat,new.sun);   
END

*/



















