<?php

/*
 * mobile-cells-to-mysql
 *
 * This script will convert mobile networks cell towers information data
 * from a well-known CSV format to SQL script intented for loading into
 * a MySQL (or compatible) database
 *
 * You can freely download the CSV data from these places:
 *   Mozilla Location Service - https://location.services.mozilla.com/
 *   OpenCelliD               - https://opencellid.org/
 *
 * Please make sure that the input file is downloaded from a trusted
 * place, there are no checks for possible SQL injection attacks.
 *
 * https://github.com/0xebef/cells-to-mysql
 *
 * License: GPLv3 or later
 *
 * Copyright (c) 2017, 0xebef
 */

define('ARG_SELF', 0);
define('ARG_INPUT_FILE', 1);
define('ARG_OUTPUT_FILE', 2);
define('ARG_MCC_FILTER', 3);

if ($argc < 3) {
    die("Usage: php {$argv[ARG_SELF]} <input-csv-file> " .
        "<output-sql-file> [mcc-filter]\n");
}

set_time_limit(0);

define('RADIO', 0);
define('MCC', 1);
define('NET', 2);
define('AREA', 3);
define('CELL', 4);
define('UNIT', 5);
define('LON', 6);
define('LAT', 7);
define('RANGE', 8);
define('SAMPLES', 9);
define('CHANGEABLE', 10);
define('CREATED', 11);
define('UPDATED', 12);
define('SIGNAL', 13);
define('COLUMNS_COUNT', 14);

/* limit the number of queries in a single transaction */
define('TRANSACTION_QUERIES_LIMIT', 5000);

/* open the input file */
$fi = fopen($argv[ARG_INPUT_FILE], 'r');
if ($fi === false) {
    die("Can not open the '{$argv[ARG_INPUT_FILE]}' input file\n");
}

/* open the output file */
$fo = fopen($argv[ARG_OUTPUT_FILE], 'w');
if ($fo === false) {
    fclose($fi);
    die("Can not open the '{$argv[ARG_OUTPUT_FILE]}' output file\n");
}

/* find out whether there is an MCC filter set */
$mcc_filter = isset($argv[ARG_MCC_FILTER])
    ? intval($argv[ARG_MCC_FILTER])
    : 0;

q($fo, "START TRANSACTION");

/* the number of queries in the current transaction */
$transaction_q_cnt = 0;

/*
 * the main loop
 */
while (($data = fgetcsv($fi)) !== false) {
    if (count($data) !== COLUMNS_COUNT || !is_numeric($data[MCC]) ||
            ($mcc_filter !== 0 && intval($data[MCC]) !== $mcc_filter)) {
        continue;
    }

    if ($data[UNIT] == '') {
        $data[UNIT] = 'NULL';
    }

    if ($data[SIGNAL] == '') {
        $data[SIGNAL] = 'NULL';
    }

    q($fo, "INSERT INTO `cells` (
        `radio`,
        `mcc`,
        `net`,
        `area`,
        `cell`,
        `unit`,
        `lon`,
        `lat`,
        `range`,
        `samples`,
        `changeable`,
        `created`,
        `updated`,
        `averageSignal`
      ) VALUES (
        '{$data[RADIO]}',
        {$data[MCC]},
        {$data[NET]},
        {$data[AREA]},
        {$data[CELL]},
        {$data[UNIT]},
        {$data[LON]},
        {$data[LAT]},
        {$data[RANGE]},
        {$data[SAMPLES]},
        {$data[CHANGEABLE]},
        {$data[CREATED]},
        {$data[UPDATED]},
        {$data[SIGNAL]}
      )");

    if ($transaction_q_cnt++ > TRANSACTION_QUERIES_LIMIT) {
        q($fo, "COMMIT");
        q($fo, "START TRANSACTION");
        $transaction_q_cnt = 0;
    }
}

q($fo, "COMMIT");

/* close the open files */
fclose($fo);
fclose($fi);

exit(0);

function q($f, $q)
{
    /* clean the query from newlines and unnecessary whitespaces */
    $q = preg_replace('/[\r\n]/u', '', $q);
    $q = preg_replace('/[ ]{2,}/u', ' ', $q);

    /* write the query, a semicolon and a newline */
    fwrite($f, $q);
    fwrite($f, ";\n");
}
