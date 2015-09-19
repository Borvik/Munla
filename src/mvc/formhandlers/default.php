<?php

class defaultFormHandler extends formHandler{
    
    public static function form($values){
        log::debug($values);
        return '-Registered';
    }
    
}