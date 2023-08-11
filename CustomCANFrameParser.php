<?php

class CustomCANFrameParser 
{
    public function __construct()
    {
    }

    public function setDBCFile($dbcFilePath)
    {
        $this->_dbcFilePath = $dbcFilePath;
        if (file_exists($this->_dbcFilePath)) {
            $this->_dbcData = file_get_contents($dbcFilePath);
            $this->_dbcData = json_decode($this->_dbcData);
        } else {
            throw new Exception("Dbc file not found.");
        }
    }

    public function setCANID($CANID)
    {
        $this->_CANID = $CANID;
        $this->_decodedSignals = (object) [];
    }

    public function setCANMessage($CANMessage)
    {
        $this->_CANMessage = self::formatCANFrame($CANMessage);
    }

    public function decodeCANFrame()
    {

        $this->_CANIDdec = hexdec($this->_CANID);
        foreach ($this->_dbcData->params as $canMessagesInDBC) {
            if ($canMessagesInDBC->canId == $this->_CANIDdec or $canMessagesInDBC->canId == $this->_CANIDdec+2147483648) {
                $this->_canMessagesContent = $canMessagesInDBC;
                $this->_CANMessage = strrev($this->_CANMessage->binnary);
                foreach ($canMessagesInDBC->signals as $signal) {
                    $this->_decodedSignals->{$signal->name} = (object) [];
                    $this->_decodedSignals->{$signal->name}->value = bindec(
                        strrev(
                            substr(
                                $this->_CANMessage,
                                $signal->startBit,
                                $signal->bitLength
                            )
                        )
                    );
                    $this->_decodedSignals->{$signal->name}->factor =
                        $signal->factor;
                    $this->_decodedSignals->{$signal->name}->offset =
                        $signal->offset;
                    $this->_decodedSignals->{$signal->name}->min = $signal->min;
                    $this->_decodedSignals->{$signal->name}->max = $signal->max;
                    $this->_decodedSignals->{$signal->name}->sourceUnit =
                        $signal->sourceUnit;
                    $this->_decodedSignals->{$signal->name}->CANMessageName =
                        $canMessagesInDBC->name;
                        $this->_decodedSignals->{$signal->name}->CANMessageID =
                        $this->_CANID;
                    $this->_decodedSignals->{$signal->name}->SignalName =
                         $signal->name;
                    $this->_decodedSignals->{$signal->name}->CalculatedValue =
                         $this->_decodedSignals->{$signal->name}->value * $signal->factor  + $signal->offset;


                }
                break;
            }
        }
        unset($this->_CANID);
        unset($this->_CANMessage);

        return $this->_decodedSignals;
    }


    public function hex2binConv($hex) {
        $table = array('0000', '0001', '0010', '0011',
                    '0100', '0101', '0110', '0111',
                    '1000', '1001', 'a' => '1010', 'b' => '1011',
                    'c' => '1100', 'd' => '1101', 'e' => '1110',
                    'f' => '1111');
        $bin = '';

        for($i = 0; $i < strlen($hex); $i++) {
            $bin .= $table[strtolower(substr($hex, $i, 1))];
        }

        return $bin;
    }

    /**
        Message formatter function
        Input : { "10ff00f0": "1-0-0-0-0-0-0-0-" }
        Input : { "10ff00f0": "1-0-0-0-0-0-0-0" }
        Byte divider here is '-'.
    **/

    public function formatCANFrame($Message)
    {
        $msg_bytes = explode("-", $Message);

        foreach ($msg_bytes as $c_msg_bytes) {
            $msg_bytes_e[] = strtoupper(
                str_pad($c_msg_bytes, 2, "0", STR_PAD_LEFT)
            );
        }

        $msg_in_hex = implode("", array_reverse($msg_bytes_e));
        return (object) [
            "hex" => $msg_in_hex,
            "binnary" =>$this->hex2binConv($msg_in_hex),
        ];
    }


}
