<?php

namespace Infrastructure\Models;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use Infrastructure\Annotations\Validation;
use Infrastructure\Exceptions\InfrastructureException;
use Infrastructure\Services\BaseService;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Time;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class ValidationRulesReader
{
    /**
     * @var BaseService
     */
    private $controllerForValidation;

    /**
     * @var string
     */
    private $methodForValidation;

    /**
     * @var array
     */
    private $rules;

    /**
     * ValidationRulesReader constructor.
     * @param BaseService $controllerForValidation
     * @param $methodForValidation
     */
    public function __construct(BaseService $controllerForValidation, $methodForValidation)
    {
        $this->controllerForValidation = $controllerForValidation;
        $this->methodForValidation = $methodForValidation;
    }

    /**
     * @return ValidationRule[]
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function rules() : array
    {
        if ($this->rules) {
            return $this->rules;
        }

        $reflectionClass = new ReflectionClass($this->controllerForValidation);
        $method = $reflectionClass->getMethod($this->methodForValidation);

        $this->rules = (new AnnotationReader())->getMethodAnnotations($method);
        return $this->rules;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function validationFields() : array
    {
        $validationFields = [];

        /** @var Validation $rule */
        foreach ($this->rules() as $rule) {
            $validationFields[] = $rule->name;
         }

         return $validationFields;
    }

    /**
     * @param $type
     * @return Type
     * @throws InfrastructureException
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    private function addType($type) : Type
    {
        $supportedTypes = ['array', 'bool', 'callable', 'float', 'double', 'int', 'integer',
            'iterable', 'long', 'null', 'numeric', 'object', 'real', 'resource', 'scalar', 'string'];

        $additionalTypes = [
            'date' => function() {return new Date();},
            'dateTime' => function() {return new DateTime();},
            'time'=> function() {return new Time();}
        ];

        if (array_key_exists($type, $additionalTypes)){
            return $additionalTypes[$type]();
        }

        if (!\in_array($type, $supportedTypes, true)){
            throw new InfrastructureException('Unsupported type for validation');
        }

        return new Type(['type' => $type]);
    }

    /**
     * @return array
     * @throws InfrastructureException
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    private function constainsMap()
    {
        return[
            'type' => function($value) {return $this->addType($value);},
            'required' => function($value) {return new NotBlank();},
            'minLength' => function($value) {return new Length(['min' => $value]);},
            'maxLength' => function($value) {return new Length(['max' => $value]);},
        ];
    }

}