<?php

/*DOCKER ---> docker run -it --rm --name proyecto-test -v ${PWD}:/usr/src/myapp -w /usr/src/myapp php:7.4-cli php importar.php 
-host=localhost -newsite=wordpress33 -dbuser=root -pass= -destino=C:/xampp/htdocs
            */

// Para la linea de comando o desde Power Shell:
// php importar.php -host=localhost -newsite=wordpress33 -dbuser=root -pass= -destino=C:/xampp/htdocs

// Para el navegador:
// http://localhost/scripts/importar_l.php?-host=localhost&-destino=C:/xampp/htdocs&-newsite=nuevositio&-dbuser=root&-pass=

// necesario para hacer funcionar los scripts por powershell

if(isset($argv)){
    foreach ($argv as $key => $arg) {
        if($key > 0){
            $e=explode("=",$arg);
            if(count($e)==2){
                $_GET[$e[0]]=$e[1];
            }else {
                $_GET[$e[0]]=0;
            }
        }
    }
}

    //rellenar con todos los campos que queramos
    if(isset($_GET['-help'])){ //necesario el --help para funcionar
        // php .\importar.php -help  
        print("Comando importar.php
        Añade los siguientes argumentos:
            -host=localhost: Indica el host de la base de datos de wordpress a exportar
            -dbuser=root: Indica el nombre del usuario de la base de datos
            -pass=password Indica la clave del usuario            
            -destino: Indica la ruta de ubicación del nuevo sitio
            -newsite=wordpress33 Indica el nombre de la nueva pagina de wordpress
        ");
        die;
    }

    // host
    if(isset($_GET['-host'])) { //servidor
        $host = $_GET['-host']; //'localhost';
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -host es obligatorio y debe indicar el nombre del servidor. Ej
        -host=localhost
        ");
        die;
    }

    // destino
    if(isset($_GET['-destino'])) { //destino
        $destino = $_GET['-destino'];  // por ejemplo: 'C:/xampp/htdocs';
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -destino es obligatorio y debe indicar la ruta del directorio destino. Ej
        -destino=C:/xampp/htdocs
        ");
        die;
    }

    //importar el sql
    if(isset($_GET['-newsite'])) {  //nombre del nuevo sitio de wordpress
        $newsite = $_GET['-newsite']; //'wordpress33';
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -newsite es obligatorio y debe indicar el nombre de la nueva base de datos. Ej
        -newsite=wordpress33
        ");
        die;
    }

   
    //informacion relevante
    if(isset($_GET['-dbuser'])) {
        $dbuser      = $_GET['-dbuser'];
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -dbuser es obligatorio y debe indicar el usuario de la base de datos. Ej
        -dbuser=root
        ");
        die;
    }

    if(isset($_GET['-pass'])) {
        $pass = $_GET['-pass'];
    } else {
        print("El argumento -pass es obligatorio y debe indicar la clave del usuario. Ej
        -pass=contraseña
        ");
        die;
    }


$comprimido = 'wordpress_bkp.zip';
$copia = '/sql/mybackup.sql';   // debe estar dentro de $destino: $destino . $copiaç
$file_path = $destino . '/' . $newsite . $copia; //donde esta el backup del sql

//Extender tiempo para garantizar ejecución completa de compresión
	ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
	set_time_limit(300);

//Descomprimirmos el zip
	$zip = new ZipArchive;
	if ($zip->open($comprimido) === TRUE) {
		$zip->extractTo($destino.'/'. $_GET['-newsite']);
		$zip->close();
		echo 'ok';
	} else {
		echo 'fallo';
	}

$conn2 = mysqli_connect($host, $dbuser, $pass);
mysqli_query($conn2, "create database " . $newsite.";");  // Crea la base de datos para el newsite
$conn = mysqli_connect($host, $dbuser, $pass, $newsite);

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
    } else {
    	echo  "<br />" . "no hay archivo"; 
    } 
}

// ajustar wp-config.php
//read the entire string
$str=file_get_contents($file_path);    // Guarda es $str el contenido de mybackup.sql
$str=str_replace('/wordpress"', '/'.$_GET['-newsite'].'"', $str);
file_put_contents($file_path, $str);    // Actualizar contenido de mybackup.sql

restoreMysqlDB($file_path, $conn);    // Restaura el backup del sql

$str=file_get_contents($destino . '/' . $newsite . '/wp-config.php');
//replace something in the file string - this is a VERY simple example
$str=str_replace('\'wordpress\'', '\''.$_GET['-newsite'].'\'', $str);

//write the entire string
file_put_contents($destino . '/' . $newsite . '/wp-config.php', $str);

?>