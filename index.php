<?php

function endsWith($haystack, $needle) {
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function element_attributes($element_name, $xml) {
    if ($xml == false) {
        return false;
    }
    $found = preg_match('#<' . $element_name . '\s+([^>]+(?:"|\'))\s?/?>#', $xml, $matches);
    if ($found == 1) {
        $attribute_array = array();
        $attribute_string = $matches[1];
        $found = preg_match_all('#([^\s=]+)\s*=\s*(\'[^<\']*\'|"[^<"]*")#', $attribute_string, $matches, PREG_SET_ORDER);
        if ($found != 0) {
            foreach ($matches as $attribute) {
                $attribute_array[$attribute[1]] = substr($attribute[2], 1, -1);
            }
            return $attribute_array;
        }
    }
    return false;
}

function getTag($xml, $tagname, $single = false) {
    $tag = preg_quote($tagname);
    if ($single) {
        preg_match_all('/<' . $tag . '.*?\/>/s', $xml, $matches, PREG_PATTERN_ORDER | PREG_SPLIT_NO_EMPTY);
    } else {
        preg_match_all('/<' . $tag . '.*?>(.*?)<\/' . $tag . '>/s', $xml, $matches, PREG_PATTERN_ORDER | PREG_SPLIT_NO_EMPTY);
    }
    return $matches;
}

function getExpression($string) {
    preg_match_all("/[^\d\.]([\d]*e-[\d+])|([\d]{1,}\.{1}[\d]*e-[\d+])/", $string, $matches, PREG_PATTERN_ORDER | PREG_SPLIT_NO_EMPTY);
    return $matches[0];
}

function checkVectorDrawable($xml, $tagname) {
    $matches = getTag($xml, $tagname);
    return $matches[1];
}

function patchFile($filepath) {
    if (!endsWith($filepath, ".xml") || !file_exists($filepath)) {
        return false;
    }

    $file = file_get_contents($filepath, true);
    if (count(($vector = checkVectorDrawable($file, "vector"))) > 0) {
        $path_tags = getTag($vector[0], "path", true);
        $fixed = false;
        foreach ($path_tags[0] as $value) {
            $attribute_array = element_attributes('path', $value);
            if ($attribute_array == false)
                continue;
            if (array_key_exists('android:pathData', $attribute_array)) {
                $expressions = getExpression($attribute_array['android:pathData']);
                $string = $attribute_array['android:pathData'];
                foreach ($expressions as $expr) {
                    $expr = trim($expr, " ");
                    $result = sprintf('%f', doubleval($expr));
                    echo "$expr = $result\n";
                    $file = str_replace("$expr", "$result", $file);
                    $fixed = true;
                }
            }
        }
        file_put_contents($filepath, $file);
        if ($fixed) {
            echo "$filepath fixed\n";
        }
        return true;
    }
    return false;
}

if (count($argv) > 1) {
    for ($i = 1; $i < count($argv); ++$i) {
        $path = $argv[$i];
        echo "process $path\n";
        if (is_dir($path)) {
            $dh = opendir($path);
            while (false !== ($filename = readdir($dh))) {
                patchFile($path . DIRECTORY_SEPARATOR . $filename);
            }
        } else {
            patchFile($path);
        }
    }
} else {
    echo "run \"php " . $argv[0] . " file or dir \"";
}
?>