<?php 

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
    if(isset($_GET['--help'])){
        print("Comando exportar
        Añade los siguientes argumentos:
            -host=host: Indica el host de la base de datos de wordpress a exportar
        ");
        die;
    }
//exportar el sql
    $new_site = 'wordpress33';
    $comprimido = 'wordpress_bkp.zip';

    //ENTER THE RELEVANT INFO BELOW
    if(isset($_GET['-dbuser'])){
        $mysqlUserName      = $_GET['-dbuser'];
    }else{
        print("El argumento -dbuser es obligatoro y debe indicar el usiao de la base de datos. Ej
        -dbuser=root
        ");
        die;
    }
    $mysqlPassword      = "";
    $mysqlHostName      = "localhost";
    $DbName             = "wordpress";
    $backup_name        = "mybackup.sql";
    $tables             = "Your tables";
    // Extender tiempo para garantizar ejecución completa de compresión
    ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
    set_time_limit(300);

   //or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables

    Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,  $tables=false, $backup_name, $mysqlHostName, $DbName, $new_site);

    function Export_Database($host,$user,$pass,$name,  $tables=false, $backup_name=false, $mysqlHostName, $DbName, $new_site)
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

        // Reemplazar en $content /localhost/wordpress por  '/localhost/' . $new_site
        $content = str_replace($mysqlHostName . '/' . $DbName, $mysqlHostName . '/' . $new_site, $content);
        $content.="SET FOREIGN_KEY_CHECKS=1;\n\n";
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
    
    // Zip archivo sera creado despues de cerrar
    $resultado = $zip->close();
    if ($resultado) {
        echo  "<br />" . " Archivo zip creado con éxito";
    } else {
        echo  "<br />" . " Error creando archivo zip";
    }

?>