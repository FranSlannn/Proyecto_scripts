<?php

$comprimido = 'wordpress_bkp.zip';
$destino = '../wordpress33';
$copia = '/sql/mybackup.sql';   // debe estar dentro de $destino: $destino . $copia

$db_host = 'localhost';
$db_uname = 'root';  //usuario
$db_password = ''; //contraseña
$db = 'wordpress33'; //nombre base de datos 
$file_path = $destino . $copia; //donde esta el backup del sql

// Extender tiempo para garantizar ejecución completa de compresión
ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
set_time_limit(300);

//descomprimirmos el zip
$zip = new ZipArchive;
if ($zip->open($comprimido) === TRUE) {
    $zip->extractTo($destino);
    $zip->close();
    echo 'ok';
} else {
    echo 'fallo';
}

/*$options = array(
    'db_uname' => 'root',  //usuario
    'db_password' => '', //contraseña
    'db' => 'wordpress33', //nombre base de datos 
    'file_path' => $destino . $copia //donde esta el backup del sql
);*/

$conn2 = mysqli_connect($db_host, $db_uname, $db_password);
mysqli_query($conn2, "create database " . $db.";");
$conn = mysqli_connect($db_host, $db_uname, $db_password, $db);

function restoreMysqlDB($filePath, $conn){
    $sql = '';
    $error = '';
    
    if (file_exists($filePath)) {
        $lines = file($filePath);
        
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }
            
            $sql .= $line;
            
            if (substr(trim($line), - 1, 1) == ';') {
                $result = mysqli_query($conn, $sql);
                if (! $result) {
                    $error .= mysqli_error($conn) . "n";
                }
                $sql = '';
            }
        }
        
        if ($error) {
        	echo "$error"; 
        } else {
        	echo  "<br />" . "Restauracion de base de datos completada."; 
        }
    }else{
    	echo  "<br />" . "no hay archivo"; 
    } 
}

restoreMysqlDB($file_path, $conn);

// ajustar wp-config.php
//read the entire string
$str=file_get_contents($destino . '/wp-config.php');
//replace something in the file string - this is a VERY simple example
$str=str_replace('\'wordpress\'', '\'wordpress33\'', $str);
//write the entire string
file_put_contents($destino . '/wp-config.php', $str);

?>