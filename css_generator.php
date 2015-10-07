#!/usr/bin/php
<?php
function check_args($argv, $argc)
{
    if ($argc > 1)
        return $folder = $argv[$argc - 1];
    else
        exit("ERREUR : Veuillez donner le nom d'un dossier à utiliser.\n");
}

function check_options($options)
{
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
        $parameters[1] .= ".png";
    if(preg_match("/.\/./", $parameters[1]))
        exit("ERREUR : Veuillez entrer un nom de sprite valide\n");

    if (!preg_match("/.*\.css$/", $parameters[2]))
        $parameters[2] .= ".css";
    if(preg_match("/.\/./", $parameters[2]))
        exit("ERREUR : Veuillez entrer un nom de feuille de style valide\n");

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
        $folder{strlen($folder)} = "/";

    if (is_dir($folder))
        $images = scan_folder("/" .   $folder, $recursive);
    else
        exit("Erreur : Veuillez fournir un dossier valide !\n");

    if (count($images) != 0)
        return $images;
    else
        exit("Le dossier ne contient aucune image PNG !\n");
}

function get_images_info($images)
{
    $info_images= array();
    $i = 0;
    foreach ($images as $image)
    {
        array_push($info_images, getimagesize($image));
        array_push($info_images[$i], $image);
        $i++;
    }
    return ($info_images);
}

function resize_images($parameters, $info_images)
{
    $images_r2u = array();
    $i = 0;
    foreach ($info_images as $key => $file)
    {
        $image_dest = imagecreatetruecolor($parameters[4], $parameters[4]);
        $image_source = imagecreatefrompng($file[4]);

        imagesavealpha($image_dest, true);
        $alpha = imagecolorallocatealpha($image_dest, 0, 0, 0, 127);
        imagefill($image_dest, 0, 0, $alpha);

        imagecopyresized($image_dest, $image_source, 0, 0, 0, 0, $parameters[4], $parameters[4], $file[0], $file[1]);
        imagedestroy($image_source);
        array_push($images_r2u, $image_dest);
        $i++;
    }
    return $images_r2u;
}

function stock_images($parameters, $info_images)
{
    $images_r2u = array();
    foreach ($info_images as $key => $file)
    {
            array_push($images_r2u, imagecreatefrompng($file[4]));
    }
    return $images_r2u;
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
            exit("ERREUR : Veuillez spécifier un nombre de colonne possible\n");
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
                    $column_height[$k] += $info_images[$i][1];
                    $i++;
                    if ($i == $nb_images)
                        break;
                }
            }
    }
    $sprite_dimension["height"] = max($column_height);
    return $sprite_dimension;
}

function create_sprite($info_images, $parameters, $sprite_dimension, $images_r2u)
{
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

    $sprite = imagecreatetruecolor($sprite_dimension["width"], $sprite_dimension["height"]);
    imagesavealpha($sprite, true);
    $alpha = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
    imagefill($sprite, 0, 0, $alpha);

    $i = $j = $l = 0;
    $k = 1;
    $start_x = 0;
    $start_y = 0;

    $choice = true;
    if (file_exists($parameters[2]))
    {
        while ($choice == true)
        {
            echo "Le fichier $parameters[2] existe déjà, voulez vous l'écraser ? (O)ui / (N)on \n";
            while ($input = fgets(STDIN))
            {
                $input = strtolower(trim($input));
                if ($input === "o" || $input === "oui")
                {
                    $css = fopen($parameters[2], 'w');
                    $choice = false;
                    break;
                }
                elseif($input === "n" || $input === "non")
                {
                    imagedestroy($sprite);
                    exit("Arret du générateur...\n");
                }
                else
                    break;
            }
        }
    }
    else
        $css = fopen($parameters[2], 'w');

    fwrite($css, '.sprite {' . "\n\t" . 'width: '. $sprite_dimension["width"] .'px;' . "\n\t" . 'height: '.$sprite_dimension["height"].'px;' . "\n\t" . 'background-image: url('.$parameters[1].');' . "\n\t" . 'background-repeat: no-repeat;' . "\n" . '}'."\n");

    foreach($info_images as $key => $file)
    {
        preg_match("/.*\/(.*)\./", $file[4], $img_name);
        fwrite($css,'.sprite-' . $img_name[1] . "\n" .' {' . "\n\t" . 'background-position: -'. $start_x .'px -'. $start_y. 'px;' . "\n\t" . 'width: '. ($file[0]/* - $parameters[3]*/) . 'px;' . "\n\t" . 'height: '. ($file[1] /*- $parameters[3]*/). 'px;' . "\n" . '}'."\n");

        if ($l < $nb_images  - 1)
        {
            $file[0] += $parameters[3];
            $file[1] += $parameters[3];
            $l++;
        }

        imagecopy($sprite, $images_r2u[$i], $start_x , $start_y, 0, 0, $file[0], $file[1]);
        $i++;

        if ($nb_lines > 0)
        {
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
            $start_x += $file[0];
    }
    fclose($css);

    $choice = true;
    if (file_exists($parameters[1]))
    {
        while ($choice == true)
        {
            echo "Le fichier $parameters[1] existe déjà, voulez vous l'écraser ? (O)ui / (N)on \n";
            while ($input = fgets(STDIN))
            {
                $input = strtolower(trim($input));
                if ($input === "o" || $input === "oui")
                {
                    imagepng($sprite,$parameters[1]);
                    $choice = false;
                    break;
                }
                elseif($input === "n" || $input === "non")
                    exit("Arret du générateur...\n");
                else
                    break;
            }
        }
    }
    else
        imagepng($sprite,$parameters[1]);

    destroy_everything($sprite, $images_r2u);
    return true;
}

function destroy_everything($sprite, $images_r2u)
{
    imagedestroy($sprite);

    foreach ($images_r2u as $key => $image)
    {
        imagedestroy($image);
    }
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

$images = array();

$options = getopt("ri:s:p:o:c:", array("recursive", "output-image:", "output-style:", "padding:", "override-size:", "columns_number:"));
$folder  = check_args($argv, $argc);
$parameters = check_options($options);
check_folder($folder, $parameters[0]);
$info_images = get_images_info($images);

if ($parameters[4] > 0)
{
    $images_r2u = resize_images($parameters, $info_images);
    for ($i=0; $i < count($info_images); $i++)
    {
        $info_images[$i][0] = $parameters[4];
        $info_images[$i][1] = $parameters[4];
    }
}
else
    $images_r2u = stock_images($parameters, $info_images);

$sprite_dimension = figure_sprite_width($info_images, $parameters);
// debug($sprite_dimension, $parameters, $info_images);

if(create_sprite($info_images, $parameters, $sprite_dimension, $images_r2u))
    exit("Votre sprite et sa feuille de style ont bien été généré. Bonne journée :)\n");
else
    exit("Une erreur est survenue. Merci de contacter l'Architecte.\n");
?>