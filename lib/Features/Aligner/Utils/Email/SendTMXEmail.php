<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 22/10/2018
 * Time: 12:38
 */

namespace  Features\Aligner\Utils\Email;

use Features\Aligner;

class SendTMXEmail extends AbstractEmail {


    protected $url ;
    protected $job;
    protected $project;
    protected $email;
    protected $user;


    public function __construct( $job, $project, $email, $user = null ) {

        $this->url = Aligner\Utils\Routes::downloadTMX($job->id, $job->password) ;
        $this->job = $job;
        $this->project = $project;
        $this->email = $email;
        $this->user = $user ;



        $this->_setLayout('skeleton.html');
        $template = Aligner::getPluginBasePath() . '/Features/Aligner/View/Emails/send_tmx.html';
        $this->_setTemplateByPath($template);
    }

    public function send() {

        if ( !empty( $this->user ) ) {
            $recipient = [ $this->user->email, $this->user->first_name ];
        } else {
            $recipient = [ $this->email, $this->email ];
        }


        $this->doSend( $recipient, $this->title,
                $this->_buildHTMLMessage(),
                $this->_buildTxtMessage( $this->_buildMessageContent() )
        );
    }

    protected function _getTemplateVariables() {

        return [
                'user'    => ( !empty( $this->user ) ) ? $this->user->toArray() : null,
                'job'     => $this->job,
                'project' => $this->project->toArray(),
                'url'     => $this->url
        ];
    }

}
