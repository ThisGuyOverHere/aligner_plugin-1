<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 16/11/2018
 * Time: 16:25
 */


namespace Features\Aligner\Controller\Validators;

/**
 * @deprecated use Validators\ChunkPasswordValidator
 */

use API\V2\Exceptions\NotFoundException;
use API\V2\KleinController;
use API\V2\Validators\Base;
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Jobs_JobStruct;

class JobPasswordValidator extends Base {
    /**
     * @var Jobs_JobStruct
     */
    private $jStruct;

    /**
     * @var KleinController
     */
    protected $controller;


    public function __construct( KleinController $controller ) {

        parent::__construct( $controller->getRequest() );
        $this->controller = $controller;

        $id_job  = $this->controller->params[ 'id_job' ];
        $password  = $this->controller->params[ 'password' ];
        $this->jStruct     = Jobs_JobDao::getByIdAndPassword( $id_job, $password );
    }

    /**
     * @return mixed|void
     * @throws NotFoundException
     */
    protected function _validate() {

        if ( empty( $this->jStruct ) ) {
            throw new NotFoundException( "Not Found.", 404 );
        }

    }

    /**
     * @return Jobs_JobStruct
     */
    public function getJob() {
        return $this->jStruct;
    }

}
