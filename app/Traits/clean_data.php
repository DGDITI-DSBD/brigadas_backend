<?php
namespace App\Traits;

use DB;


trait clean_data 
{
    public function cleanBOM($text)
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $text = preg_replace('/^[\x00-\x1F]+/', '', $text);
        return trim($text);
    }

    public function convertBytes($val){
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val  = (int)$val;

        switch ($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }

        return $val;
    }

    public function maxBatchBytes(){
        return 5 * 1024 * 1024; // 5MB por lote
    }

    public function recalculate_rows_per_params($name_table){
        $count_columns = DB::getSchemaBuilder()->getColumnListing($name_table);
        $chunk = floor(65535 / count($count_columns));
        return $chunk;
    }
}