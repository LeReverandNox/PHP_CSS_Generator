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
        exit("\033[31m ERREUR \033[0m: Veuillez donner le nom d'un dossier à utiliser.\n");
    }
}

function check_options($options)
{
    $parameters = array(false, "sprite.png", "style.css", 0, 0, 0, NULL);

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
        $parameters[3] = $options["p"];
    if (isset($options["padding"]))
        $parameters[3] = $options["padding"];

    if (isset($options["o"]))
        $parameters[4] = $options["o"];
    if (isset($options["override-size"]))
        $parameters[4] = $options["override-size"];

    if (isset($options["c"]))
        $parameters[5] = $options["c"];
    if (isset($options["columns_number"]))
        $parameters[5] = $options["columns_number"];

    if (isset($options["a"]))
        $parameters[6] = $options["a"];
    if (isset($options["alpha"]))
        $parameters[6] = $options["alpha"];

    if (is_array($parameters[1]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir un seul nom de sprite !\n");

    if (is_array($parameters[2]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir un seul nom de feuille de style !\n");

    if (is_array($parameters[3]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir un seul padding !\n");
    else
        $parameters[3] = (int)$parameters[3];

    if (is_array($parameters[4]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir une seule taille de redimensionnement !\n");
    else
        $parameters[4] = (int)$parameters[4];

    if (is_array($parameters[5]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir un seul nombre de colonnes !\n");
    else
        $parameters[5] = (int)$parameters[5];

    if (is_array($parameters[6]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir une seule couleur !\n");
    elseif (isset($parameters[6]) && !ctype_xdigit($parameters[6]))
        exit("\033[31m ERREUR \033[0m: Veuillez fournir une couleur héxadécimal valide !\n");

    if (!preg_match("/.*\.png$/", $parameters[1]))
        $parameters[1] .= ".png";
    if(preg_match("/.\/./", $parameters[1]))
        exit("\033[31m ERREUR \033[0m: Veuillez entrer un nom de sprite valide\n");

    if (!preg_match("/.*\.css$/", $parameters[2]))
        $parameters[2] .= ".css";
    if(preg_match("/.\/./", $parameters[2]))
        exit("\033[31m ERREUR \033[0m: Veuillez entrer un nom de feuille de style valide\n");

    return ($parameters);
}

function convert_hex_to_rgb($hexa)
{
   if (strlen($hexa) == 6)
   {
        $r = hexdec(substr($hexa,0,2));
        $g = hexdec(substr($hexa,2,2));
        $b = hexdec(substr($hexa,4,2));
   }
   else
   {
        $r = hexdec(substr($hexa, 0, 1) . substr($hexa, 0, 1));
        $g = hexdec(substr($hexa, 1, 1) . substr($hexa, 1, 1));
        $b = hexdec(substr($hexa, 2, 1) . substr($hexa, 2, 1));
   }
   $rgb = array($r, $g, $b);
   return $rgb;
}

function check_writable()
{
    $current_dir = getcwd();
    if (!is_writable($current_dir))
    {
        exit("\033[31m ERREUR \033[0m: Vous n'avez pas les droits d'écriture sur le répertoire courant.\n");
    }
}

function scan_folder($directory, $recursive)
{
    global $images;
    if ($directory{0} != "/")
    {
        $directory = "/" . $directory;
        $relative = "." . $directory;
    }
    else
    {
        $relative = $directory;
    }

    if(is_readable($relative) && $dh = opendir($relative))
    {
        while(false !== ($file = readdir($dh)))
        {
            if(($file !== ".") && ($file !== ".."))
            {
                if(!is_dir($relative . $file) && is_readable($relative . $file))
                {
                    if (pathinfo($file, PATHINFO_EXTENSION) == "png")
                    {
                        if ($relative{0} == "/")
                        {
                            array_push($images, $directory . $file);
                        }
                        else
                        {
                            array_push($images, "." . $directory . $file);
                        }
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

    if (is_dir($folder) && is_readable($folder))
    {
             $images = scan_folder($folder, $recursive);
    }
    else
    {
        exit("\033[31m ERREUR \033[0m: Veuillez fournir un dossier valide !\n");
    }

    if (count($images) != 0)
    {
        return $images;
    }
    else
    {
        exit("\033[31m ERREUR \033[0m: Le dossier ne contient aucune image PNG !\n");
    }
}

function check_png($file)
{
    if ($file_to_check = fopen($file, 'rb'))
    {
        $header = fread($file_to_check, 8);
        fclose($file_to_check);
        if (strncmp($header, "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a", 8) == 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

function get_images_info($images)
{
    $info_images= array();
    $i = 0;
    foreach ($images as $image)
    {
        if (check_png($image))
        {
            array_push($info_images, getimagesize($image));
            array_push($info_images[$i], $image);
            $i++;
        }
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
        {
            exit("\033[31m ERREUR \033[0m: Veuillez spécifier un nombre de colonne possible\n");
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
                    $column_height[$k] += $info_images[$i][1];
                    $i++;
                    if ($i == $nb_images)
                    {
                        break;
                    }
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
    {
            $nb_lines = ceil($nb_images / $column);
    }
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
            echo "\033[33m ATTENTION \033[0m: Le fichier $parameters[2] existe déjà, voulez vous l'écraser ? (O)ui / (N)on : ";
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
                {
                    break;
                }
            }
        }
    }
    else
    {
        $css = fopen($parameters[2], 'w');
    }

    fwrite($css, ".sprite {\n\twidth: " . $sprite_dimension["width"] . "px;\n\theight: " .$sprite_dimension["height"] . "px;\n\tbackground-image: url(\"" .$parameters[1]. "\");\n\tbackground-repeat: no-repeat;\n}\n");

    $img_name_list = array();
    foreach($info_images as $key => $file)
    {
        static $compteur = 0;
        preg_match("/.*\/(.*)\./", $file[4], $img_name);
        $img_name[1] = str_replace(" ", "-", $img_name[1]);
        if (in_array($img_name[1], $img_name_list))
        {
            $compteur++;
            $img_name[1] .= "-$compteur";
        }

        fwrite($css, ".sprite-" . $img_name[1] . " {\n\tbackground-position: -" . $start_x . "px -" . $start_y . "px;\n\twidth: " . $file[0] . "px;" . "\n\theight: " . $file[1]  . "px;\n}\n");

        array_push($img_name_list, $img_name[1]);
        if ($l < $nb_images  - 1)
        {
            $file[0] += $parameters[3];
            $file[1] += $parameters[3];
            $l++;
        }

        if (isset($parameters[6]))
        {
            $r = $parameters[6][0];
            $g = $parameters[6][1];
            $b = $parameters[6][2];
            $transparent_color = imagecolorexact($images_r2u[$i], $r, $g, $b);
            imagecolortransparent($images_r2u[$i], $transparent_color);
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
        {
            $start_x += $file[0];
        }
    }
    fclose($css);

    $choice = true;
    if (file_exists($parameters[1]))
    {
        while ($choice == true)
        {
            echo "\033[33m ATTENTION \033[0m: Le fichier $parameters[1] existe déjà, voulez vous l'écraser ? (O)ui / (N)on  : ";
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
                {
                    exit("Arret du générateur...\n");
                }
                else
                {
                    break;
                }
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

$options = getopt("ri:s:p:o:c:a:", array("recursive", "output-image:", "output-style:", "padding:", "override-size:", "columns_number:", "alpha:"));
$folder  = check_args($argv, $argc);
$parameters = check_options($options);

if (isset($parameters[6]))
{
    $parameters[6] = convert_hex_to_rgb($parameters[6]);
}

check_writable();
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
{
    $images_r2u = stock_images($parameters, $info_images);
}

$sprite_dimension = figure_sprite_width($info_images, $parameters);
// debug($sprite_dimension, $parameters, $info_images);

if(create_sprite($info_images, $parameters, $sprite_dimension, $images_r2u))
{
    exit("----------------------------------------------------------------\n\033[32m SUCCESS \033[0m: Votre sprite et sa feuille de style ont bien été généré.\nMerci d'avoir utilisé le Nox's Sprite Generator v1.0, bonne journée :)\n");
}
else
{
    exit("\033[31m ERREUR \033[0m: Une erreur est survenue. Merci de contacter l'Architecte.\n");
}
?>