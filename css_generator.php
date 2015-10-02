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

    return ($parameters);
}

// function check_options($options)
// {
//     if (condition)
//     {
//         arra
//     }

//     return $options;
// }

// function scan_folder($folder, $recursive)
// {
//     $scan = opendir($folder);
//     while (false !== ($entry = readdir($scan)))
//     {
//         // echo "$entry\n";
//         if (pathinfo($entry, PATHINFO_EXTENSION) == "png")
//         {
//             echo "Le fichier $entry est une image .png \n";
//         }

//         if (is_dir($folder . "/" . $entry) && $recursive)
//         {
//             if (($entry !== ".") AND ($entry !==  ".."))
//             {
//                 echo "$entry/ est un dossier\n";
//             }
//         }
//     }
// }

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
        array_push($info_images, getimagesize($image));
        array_push($info_images[$i], $image);
        $i++;
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

            // echo "Nombe de colonnes : $column " . "\n";
            for ($j=0; $j < $nb_images; $j+= $column)
            {
                // echo $j . "\n";
                for ($k=0; $k < $column; $k++)
                {
                    $column_height[$k] += $info_images[$k]["1"];
                }
            }
    }
    $sprite_dimension["height"] = max($column_height);
    // var_dump($column_height);
    return $sprite_dimension;
}


function create_sprite($info_images, $parameters)
{
    // print_r($info_images);
    // var_Dump( $parameters);
    $dst_x = 0;
    $dst_y = 0;
    $column_width = array();

    foreach ($info_images as $image => $info)
    {
        // echo $info[0] . "\n";
        if ($parameters[5] > 0)
        {
            for ($i=0; $i < $parameters[5]; $i++)
            {
                $column_width[$i] += $info[0];
            }
        }
    }
    // var_dump($column_width);
    foreach ($info_images as $image => $info)
    {
        // echo $info[4] . "\n";
        $img = imagecreatefrompng($info[4]);

        // imagecopy("./".$parameters[1], $info[4], $dst_x, $dst_y, 0, 0, $info[0], $info[1]);
    }
}

function debug($sprite_dimension, $parameters)
{
    echo "Voici les dimensions de la sprite à générer : " . $sprite_dimension["width"] . "x" . $sprite_dimension["height"]. "px\n";
    echo "Le sprite de sortie s'appelera : $parameters[1]\n";
    echo "La feuille de style s'appelera : $parameters[2]\n";
}

error_reporting(E_ALL & ~E_NOTICE);
$images = array();

$options = getopt("ri:s:p:o:c:", array("recursive", "output-image:", "output-style:", "padding:", "override-size:", "columns_number:"));
$folder  = check_args($argv, $argc);
$parameters = check_options($options);
check_folder($folder, $parameters[0]);
$info_images = get_images_info($images);
$sprite_dimension = figure_sprite_width($info_images, $parameters);
create_sprite($info_images, $parameters);
debug($sprite_dimension, $parameters);
?>