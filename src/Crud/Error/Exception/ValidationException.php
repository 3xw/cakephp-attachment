<?php
namespace Attachment\Crud\Error\Exception;

use Cake\Http\Exception\BadRequestException;
use Cake\ORM\Entity;
use Cake\Utility\Hash;

/**
 * Exception containing validation errors from the model. Useful for API
 * responses where you need an error code in response
 *
 */
class ValidationException extends BadRequestException
{

    /**
     * List of validation errors that occurred in the model
     *
     * @var array
     */
    protected $_validationErrors = [];

    /**
     * How many validation errors are there?
     *
     * @var int
     */
    protected $_validationErrorCount = 0;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Entity $entity Entity
     * @param int $code code to report to client
     */
     public function __construct(Entity $entity, $code = 422)
    {
        $this->_validationErrors = array_filter((array)$entity->getErrors());
        $flat = Hash::flatten($this->_validationErrors);
        $this->_validationErrorCount = count($flat);

        $this->message = '';

        $error_msg = [];
        foreach( $this->_validationErrors as $field => $error){
          if(is_array($error)){ foreach($error as $f => $e){ $this->message .= $field." as following error: $e."; } }else{ $this->message .= $e.'. '; }
        }

        parent::__construct($this->message, $code);
    }

    /**
     * Returns the list of validation errors
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }

    /**
     * How many validation errors are there?
     *
     * @return int
     */
    public function getValidationErrorCount()
    {
        return $this->_validationErrorCount;
    }
}
