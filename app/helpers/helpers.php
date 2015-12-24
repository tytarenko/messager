<?php

/**
 * @param array $array
 * @return mixed
 */
function array_rand_val(array $array) {
    return $array[array_rand($array)];
}