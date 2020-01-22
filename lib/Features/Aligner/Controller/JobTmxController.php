<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 19/11/2018
 * Time: 15:10
 */


namespace Features\Aligner\Controller;

use Exceptions\ValidationError;
use Features\Aligner;
use Features\Aligner\Controller\Validators\JobPasswordValidator;
use Features\Aligner\Model\Jobs_JobDao;


class JobTmxController extends AlignerController {

    protected $job;

    public function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
        } );

        $this->appendValidator( $jobValidator );
    }


    public function pushTMXInTM() {

        $config = Aligner::getConfig();

        $is_public = $this->params[ 'is_public' ];

        if ( $this->getUser() ) {
            if ( !empty( $this->params[ 'email' ] ) ) {
                $email = $this->params[ 'email' ];
            } else {
                $email = $this->user->email;
            }

            $userName    = $this->user->first_name;
            $userSurname = $this->user->last_name;
            if ( $is_public == 1 ) {
                $tm_key = $config[ 'GLOBAL_PRIVATE_TM' ];
            } else {
                $tm_key = $this->params[ 'key' ];
            }
        } else {
            $email       = $this->params[ 'email' ];
            $userName    = null;
            $userSurname = null;
            if ( $is_public == 1 ) {
                $tm_key = $config[ 'GLOBAL_PRIVATE_TM' ];
            } else {
                try {
                    $tms          = \Engine::getInstance( 1 );
                    $mymemory_key = $tms->createMyMemoryKey();
                    $tm_key       = $mymemory_key[ 'key' ];
                } catch ( \Exception $e ) {
                    throw new ValidationError( $e->getMessage() );
                }
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationError("Invalid email address");
        }


        $params = [];
        $params['job'] = $this->job;
        $params['tm_key'] = $tm_key;
        $params['email'] = $email;
        $params['first_name'] = $userName;
        $params['last_name'] = $userSurname;

        Jobs_JobDao::updateFields(['exported' => 1], $this->job->id, $this->job->password);


        try {
            \WorkerClient::init( new \AMQHandler() );
            \WorkerClient::enqueue( 'ALIGNER_TMX_IMPORT', 'Features\Aligner\Utils\AsyncTasks\Workers\TMXImportWorker', json_encode( $params ), [ 'persistent' => \WorkerClient::$_HANDLER->persistent
            ] );
        } catch ( \Exception $e ) {

            # Handle the error, logging, ...
            $output = "**** TMX Import Enqueue failed. AMQ Connection Error. ****\n\t";
            $output .= "{$e->getMessage()}";
            $output .= var_export( $params, true );
            \Log::doLog( $output );
            throw $e;

        }

        return $this->response->json( true );

    }

}