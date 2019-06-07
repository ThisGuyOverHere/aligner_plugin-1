<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 22/10/2018
 * Time: 12:45
 */


namespace Features\Aligner\Utils\Email;

use INIT ;

abstract class AbstractEmail extends \Email\AbstractEmail
{

    protected $title;

    protected $_layout_path ;
    protected $_template_path ;


    protected function _getLayoutVariables( $messageBody = null ) {

        if ( isset( $this->title ) ) {
            $title = $this->title ;
        } else {
            $title = 'MateCat' ;
        }

        return array(
                'title' => $title,
                'messageBody' => ( !empty( $messageBody ) ? $messageBody : $this->_buildMessageContent() ),
                'closingLine' => "Kind regards, ",
                'showTitle' => false
        );
    }

    protected function _getDefaultMailConf() {

        $mailConf = array();

        $mailConf[ 'Host' ]       = INIT::$SMTP_HOST;
        $mailConf[ 'port' ]       = INIT::$SMTP_PORT;
        $mailConf[ 'sender' ]     = INIT::$SMTP_SENDER;
        $mailConf[ 'hostname' ]   = INIT::$SMTP_HOSTNAME;

        $mailConf[ 'from' ]       = INIT::$SMTP_SENDER;
        $mailConf[ 'fromName' ]   = INIT::$MAILER_FROM_NAME;
        $mailConf[ 'returnPath' ] = INIT::$MAILER_RETURN_PATH;

        return $mailConf ;

    }

    protected function sendTo($address, $name){
        $recipient = [ $address, $name ];

        $this->doSend( $recipient, $this->title,
                $this->_buildHTMLMessage(),
                $this->_buildTxtMessage( $this->_buildMessageContent() )
        );
    }

    protected function doSend($address, $subject, $htmlBody, $altBody) {
        $mailConf = $this->_getDefaultMailConf();

        $mailConf[ 'address' ] = $address ;
        $mailConf[ 'subject' ] = $subject ;

        $mailConf[ 'htmlBody' ] = $htmlBody ;
        $mailConf[ 'altBody' ]  = $altBody ;

        $this->_enqueueEmailDelivery( $mailConf );

        return true;
    }

}