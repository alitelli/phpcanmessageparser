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
            if ($canMessagesInDBC->canId == $this->_CANIDdec) {
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
                        
                        
                }
                break;
            }
        }
        unset($this->_CANID);
        unset($this->_CANMessage);
        
        return $this->_decodedSignals;
    }

    public function formatCANFrame($Message)
    {
        $msg_bytes = explode("-", $Message);
        $exp_element_c = is_countable($msg_bytes) ? count($msg_bytes) : 8 ;
        if( $exp_element_c == 9) array_pop($msg_bytes);
        foreach ($msg_bytes as $c_msg_bytes) {
            $msg_bytes_e[] = strtoupper(
                str_pad($c_msg_bytes, 2, "0", STR_PAD_LEFT)
            );
        }
        $msg_in_hex = implode("", array_reverse($msg_bytes_e));
        return (object) [
            "hex" => $msg_in_hex,
            "binnary" => str_pad(
                base_convert($msg_in_hex, 16, 2),
                64,
                "0",
                STR_PAD_LEFT
            ),
        ];
    }
}
