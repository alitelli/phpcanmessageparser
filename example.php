<?php
require_once "CustomCANFrameParser.php";
// construct library
$canBusDecoder =  new CustomCANFrameParser ();

//dbc file in converted json type
$canBusDecoder->setDBCFile( "DBCFileInJsonFormat.json" );

//can id 
$canBusDecoder->setCANID("12CD10");

//can data frame.
$canBusDecoder->setCANMessage("01-a3-00-00-00-00-00-00");

// dump extracted signal.
var_dump( $canBusDecoder->decodeCANFrame() );
