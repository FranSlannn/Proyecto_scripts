<?php 


/*DOCKER ---> docker run -it --rm --name proyecto-test -v ${PWD}:/usr/src/myapp -w /usr/src/myapp php:7.4-cli php exportar.php
 -host=localhost -dbname=wordpress -dbuser=root -pass=
 */

// Para la linea de comando o desde Power Shell:
// buscar la ruta C:\xampp\htdocs\scripts
// php exportar.php -host=localhost -dbname=wordpress -dbuser=root -pass=

// Para el navegador:
// http://localhost/scripts/exportar.php?-host=localhost&-dbname=wordpress


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

    //rellenar con todos los campos que queramos; es la ayuda que vera el usuario
    if(isset($_GET['-help'])){ //necesario el --help para funcionar
       // php .\exportar.php -help  
       
        print("Comando exportar.php 
        Añade los siguientes argumentos:
            -host=localhost: Indica el host de la base de datos de wordpress a exportar
            -dbuser=root: Indica el nombre del usuario de la base de datos
            -pass=password Indica la clave del usuario
            -dbname=wordpress Indica el nombre de la base de datos de wordpress
            
        ");
        die;
    }

    // host
    if(isset($_GET['-host'])) {  //servidor
        $mysqlHostName = $_GET['-host']; //'localhost';
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -host es obligatorio y debe indicar el nombre del servidor. Ej
        -host=localhost
        ");
        die;
    }

    if(isset($_GET['-dbname'])) { //nombre de la base de datos
        $DbName = $_GET['-dbname']; //'wordpress';
        $comprimido = $_GET['-dbname'] . '_bkp.zip';
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -dnname es obligatorio y debe indicar el nombre de la base de datos. Ej
        -dbname=wordpress
        ");
        die;
    }

    //informacion relevante
    if(isset($_GET['-dbuser'])) {
        $mysqlUserName      = $_GET['-dbuser'];
    } else {//ejemplo de como se puede cambiar los datos en el powershell
        print("El argumento -dbuser es obligatorio y debe indicar el usuario de la base de datos. Ej
        -dbuser=root
        ");
        die;
    }

    if(isset($_GET['-pass'])) {
        $mysqlPassword = $_GET['-pass'];
    } else {
        print("El argumento -pass es obligatorio y debe indicar la clave del usuario. Ej
        -pass=contraseña
        ");
        die;
    }
	//datos importantes
	
		$backup_name        = "mybackup.sql";
		$tables             = "Your tables";
    
    // Extiende tiempo para garantizar ejecución completa de compresión
    ini_set('max_execution_time', '420'); //420 seconds = 7 minutes
    set_time_limit(420);

   //or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables

    Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,  $tables=false, $backup_name, $mysqlHostName, $DbName);

    function Export_Database($host,$user,$pass,$name,  $tables=false, $backup_name=false, $mysqlHostName, $DbName)
    {
        $mysqli = new mysqli($host,$user,$pass,$name); 
        $mysqli->select_db($name); 
        $mysqli->query("SET NAMES 'utf8'");

        $queryTables    = $mysqli->query('SHOW TABLES'); 
        while($row = $queryTables->fetch_row()) 
        { 
            $target_tables[] = $row[0]; 
        }   
        if($tables !== false) 
        { 
            $target_tables = array_intersect( $target_tables, $tables); 
        }
        $content="SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach($target_tables as $table)
        {
            $result         =   $mysqli->query('SELECT * FROM '.$table);  
            $fields_amount  =   $result->field_count;  
            $rows_num=$mysqli->affected_rows;     
            $res            =   $mysqli->query('SHOW CREATE TABLE '.$table); 
            $TableMLine     =   $res->fetch_row();
            $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";
           // var_dump($content);die;

            for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
            {
                while($row = $result->fetch_row())  
                { //when started (and every after 100 command cycle):
                    if ($st_counter%100 == 0 || $st_counter == 0 )  
                    {
                            $content .= "\nINSERT INTO ".$table." VALUES";
                    }
                    $content .= "\n(";
                    for($j=0; $j<$fields_amount; $j++)  
                    { 
                        $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                        if (isset($row[$j]))
                        {
                            $content .= '"'.$row[$j].'"' ; 
                        }
                        else 
                        {   
                            $content .= '""';
                        }     
                        if ($j<($fields_amount-1))
                        {
                                $content.= ',';
                        }      
                    }
                    $content .=")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
                    {   
                        $content .= ";";
                    } 
                    else 
                    {
                        $content .= ",";
                    } 
                    $st_counter=$st_counter+1;
                }
            } $content .="\n\n\n";
        }

        $content  = str_replace( "DEFAULT '0000-00-00 00:00:00'", '', $content); //buscar y que esta vacio esto -----> NOT NULL DEFAULT '0000-00-00 00:00:00'
        $content .= "SET FOREIGN_KEY_CHECKS=1;\n\n"; //arregla errores tablas sql
        

        if(file_put_contents( $backup_name, $content)) {
            echo "Descarga exitosa " . "<br />";
        } else {
            echo "Descarga de archivo fallida." . "<br />";
        }
    }

    // comprimir sql y carpeta wordpress
   
    $path = "../wordpress";
    $zip = new ZipArchive();
    if (!$zip->open($comprimido, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        die("Error abriendo ZIP");
    }
    
    // Crear directorio iterador recursivo
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    //var_dump($relativePath);die;
    foreach ($files as $name => $file)
    {
        // Salta directorios (sera añadido automaticamente)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getPath()."/".$file->getBasename();
            //$relativePath = substr($filePath, strlen($path) + 1);
            $relativePath = str_replace($path, "", $filePath);
            // Adñadir archivo
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->addFile('./mybackup.sql', 'sql/mybackup.sql');
    
    // Archivo Zip sera creado despues de cerrar
    $resultado = $zip->close();
    if ($resultado) {
        echo  "<br />" . " Archivo zip creado con éxito";
    } else {
        echo  "<br />" . " Error creando archivo zip";
    }

?>