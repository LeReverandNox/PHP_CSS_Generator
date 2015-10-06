#!/usr/bin/php
<?php
function check_args($argv, $argc)
{
    if ($argc > 1)
    {
        return $folder = $argv[$argc - 1];
    }
    else
    {
        echo "ERREUR : Veuillez donner le nom d'un dossier à utiliser.\n";
        exit;
    }
}

function check_options($options)
{
    // var_dump($options);
    // die;
    $parameters = array(false, "sprite.png", "style.css", 0, 0, 0);
    if (isset($options["r"]) || isset($options["recursive"]))
        $parameters[0] = true;

    if (isset($options["i"]))
        $parameters[1] = $options["i"];
    if (isset($options["output-image"]))
        $parameters[1] = $options["output-image"];

    if (isset($options["s"]))
        $parameters[2] = $options["s"];
    if (isset($options["output-style"]))
        $parameters[2] = $options["output-style"];

    if (isset($options["p"]))
        $parameters[3] = (int)$options["p"];
    if (isset($options["padding"]))
        $parameters[3] = (int)$options["padding"];

    if (isset($options["o"]))
        $parameters[4] = (int)$options["o"];
    if (isset($options["override-size"]))
        $parameters[4] = (int)$options["override-size"];

    if (isset($options["c"]))
        $parameters[5] = (int)$options["c"];
    if (isset($options["columns_number"]))
        $parameters[5] = (int)$options["columns_number"];

    if (!preg_match("/.*\.png$/", $parameters[1]))
    {
        $parameters[1] .= ".png";
    }
    if(preg_match("/.\/./", $parameters[1]))
    {
        echo "ERREUR : Veuillez entrer un nom de sprite valide\n";
        exit;
    }

    if (!preg_match("/.*\.css$/", $parameters[2]))
    {
        $parameters[2] .= ".css";
    }
    if(preg_match("/.\/./", $parameters[2]))
    {
        echo "ERREUR : Veuillez entrer un nom de feuille de style valide\n";
        exit;
    }

    return ($parameters);
}

function scan_folder($directory, $recursive)
{
    global $images;
    $relative = ".".$directory;
    if($dh = opendir($relative))
    {
        while(false !== ($file = readdir($dh)))
        {
            if(($file !== ".") && ($file !== ".."))
            {
                if(!is_dir($relative . $file))
                {
                    if (pathinfo($file, PATHINFO_EXTENSION) == "png")
                    {
                        array_push($images, "." . $directory . $file);
                    }
                }
                elseif($recursive)
                {
                    scan_folder($directory.$file."/", $recursive);
                }
            }
        }
    }
    return $images;
}

function check_folder($folder, $recursive)
{
    global $images;

    if (substr($folder,  -1) !== "/")
    {
        $folder{strlen($folder)} = "/";
    }

    if (is_dir($folder))
    {
        $images = scan_folder("/" .   $folder, $recursive);
    }
    else
    {
        echo "Erreur : Veuillez fournir un dossier valide !\n";
        exit;
    }

    if (count($images) != 0)
    {
            return $images;
    }
    else
    {
        echo "Le dossier ne contient aucune image PNG !\n";
        exit;
    }
}

function get_images_info($images)
{
    $info_images= array();
    $i = 0;
    foreach ($images as $image)
    {
        if(@exif_imagetype($image))
        {
            array_push($info_images, getimagesize($image));
            array_push($info_images[$i], $image);
            $i++;
        }
    }
    return ($info_images);
}

function  figure_sprite_width($info_images, $parameters)
{
    $sprite_dimension = array("width" => 0, "height" => 0);
    $nb_images = count($info_images);
    $column = $parameters[5];
    $nb_lines = 0;

        if ($column > 0 && $column <= $nb_images)
        {
            $row_width = array();
            $nb_lines = ceil($nb_images / $column);
            for ($i=0; $i < $nb_lines; $i++)
            {
                array_push($row_width, 0);
            }

            $k = 0;
            for ($i=0; $i <  $nb_lines ; $i++)
            {
                for ($j=0; $j <  $column; $j++)
                {
                    $row_width[$i] += $info_images[$k][0];
                    $k++;
                    if ($k == $nb_images)
                    {
                        break;
                    }
                }
            }
            $sprite_dimension["width"] = max($row_width);
        }
        elseif ($column > $nb_images)
        {
            echo  "ERREUR : Veuillez spécifier un nombre de colonne possible\n";
            exit;
        }
        else
        {
            foreach ($info_images as $image => $info)
            {
                $sprite_dimension["width"] += $info[0];
            }
        }
        $sprite_dimension = figure_sprite_height($info_images, $sprite_dimension, $nb_lines, $column);
        return $sprite_dimension;
}

function figure_sprite_height($info_images, $sprite_dimension, $nb_lines, $column)
{
    $column_height =  array();
    $nb_images = count($info_images);

    if ($nb_lines == 0)
    {
        foreach ($info_images as $image => $info)
        {
           array_push($column_height, $info[1]);
        }
    }
    else
    {
            for ($i=0; $i < $column ; $i++)
            {
                array_push($column_height, 0);
            }

            $i = 0;
            for ($j=0; $j < $nb_images; $j+= $column)
            {
                for ($k=0; $k < $column; $k++)
                {
                    // echo $info_images[$i][1] . "Image numero $i\n";
                    // echo "Hauteur : " . $info_images[$i][1] . "\n";
                    $column_height[$k] += $info_images[$i][1];
                    $i++;
                    // echo "Nom : ".$info_images[$i][4] . "\n";
                    // echo "On écrit dans la colonne : $k\n";
                    // echo $column_height[$k] . "\n";
                    if ($i == $nb_images)
                    {
                        break;
                    }
                }
            }
    }
    $sprite_dimension["height"] = max($column_height);
    // var_dump($column_height);
    return $sprite_dimension;
}


function create_sprite($info_images, $parameters, $sprite_dimension)
{
    // print_r($info_images);
    // var_Dump( $parameters);
    // $dst_x = 0;
    // $dst_y = 0;
    // $column_width = array();
    $column = $parameters[5];
    $nb_images = count($info_images);
    if ($column > 0)
            $nb_lines = ceil($nb_images / $column);
    else
    {
        $nb_lines = 2;
        $column = $nb_images;
    }

    $sprite_dimension["width"] += $parameters[3] * ($column  - 1);
    $sprite_dimension["height"] += $parameters[3] * ($nb_lines - 1);
    // echo "Voici les dimensions de la sprite à générer : " . $sprite_dimension["width"] . "x" . $sprite_dimension["height"]. "px\n";

    $sprite = imagecreatetruecolor($sprite_dimension["width"], $sprite_dimension["height"]);
    imagesavealpha($sprite, true);
    $alpha = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
    imagefill($sprite, 0, 0, $alpha);

    $j = $l = 0;
    $k = 1;
    $start_x = 0;
    $start_y = 0;
    $css = fopen($parameters[2], 'w');
    fwrite($css, '.sprite {' . "\n\t" . 'width: '. $sprite_dimension["width"] .'px;' . "\n\t" . 'height: '.$sprite_dimension["height"].'px;' . "\n\t" . 'background-image: url('.$parameters[1].');' . "\n\t" . 'background-repeat: no-repeat;' . "\n" . '}'."\n");
        foreach($info_images as $key => $file)
        {
            preg_match("/.*\/(.*)\./", $file[4], $img_name);

            fwrite($css,'.sprite-' . $img_name[1] . "\n" .' {' . "\n\t" . 'background-position: -'. $start_x .'px -'. $start_y. 'px;' . "\n\t" . 'width: '. ($file[0]/* - $parameters[3]*/) . 'px;' . "\n\t" . 'height: '. ($file[1] /*- $parameters[3]*/). 'px;' . "\n" . '}'."\n");
            $image = imagecreatefrompng($file[4]);

            if ($l < $nb_images  - 1)
            {
                $file[0] += $parameters[3];
                $file[1] += $parameters[3];
                $l++;
            }

            imagecopy($sprite, $image, $start_x , $start_y, 0, 0, $file[0], $file[1]);

            // $i++;
            if ($nb_lines > 0)
            {
                // echo "Image numero : $i\n";
                // echo $start_x . "\n";
                // echo $start_y . "\n";
                // echo $k . "\n";
                $start_x += $file[0];
                if ($k == $column)
                {
                    $start_x = 0;
                    $start_y += $file[1];
                    $k = 0;
                }
                $k++;
            }
            else
            {
                $start_x += $file[0];
            }
        }
    fclose($css);
    imagepng($sprite,$parameters[1]); // Save image to file
    imagedestroy($sprite);
    return true;
}

function debug($sprite_dimension, $parameters, $info_images)
{
    foreach ($info_images as $image => $info)
    {
        echo "Fichier à traiter : ". $info[4] . "\n";
    }
    echo count($info_images) . "fichiers vont etre traiter.\n";
    echo "Voici les dimensions de la sprite à générer : " . $sprite_dimension["width"] . "x" . $sprite_dimension["height"]. "px\n";
    echo "Le sprite de sortie s'appelera : $parameters[1]\n";
    echo "La feuille de style s'appelera : $parameters[2]\n";
    echo "Avec un padding de $parameters[3] px entre les images \n";
}

// error_reporting(E_ALL & ~E_NOTICE);
$images = array();

$options = getopt("ri:s:p:o:c:", array("recursive", "output-image:", "output-style:", "padding:", "override-size:", "columns_number:"));
$folder  = check_args($argv, $argc);
$parameters = check_options($options);
check_folder($folder, $parameters[0]);
$info_images = get_images_info($images);
$sprite_dimension = figure_sprite_width($info_images, $parameters);
// debug($sprite_dimension, $parameters, $info_images);

if(create_sprite($info_images, $parameters, $sprite_dimension))
{
    echo "Votre sprite et sa feuille de styles ont bien été généré. Bonne journée :)\n";
    exit(0);
}
else
{
    echo "Une erreur est survenue. Merci de contacter l'Architecte.\n";
    exit(1);
}
?>