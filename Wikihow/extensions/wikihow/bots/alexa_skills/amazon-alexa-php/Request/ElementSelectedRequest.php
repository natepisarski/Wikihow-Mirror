<?php

namespace Alexa\Request;

use Symfony\Component\Validator\Constraints as Assert;

use Alexa\Utility\Purifier\HasPurifier;

/**
 * Class ElementSelectedRequest
 *
 * Represents an Alexa Display.ElementSelected request
 *
 * @package Alexa\Request
 */
class ElementSelectedRequest extends BaseRequest
{
    // Traits

    use HasPurifier;

    // Constants

    const ERROR_TOKEN_VALUE_NOT_SET = 'The token was not set in the request';

    // Fields

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    private $token;


    // Hooks

    /**
     * ElementSelectedRequest()
     *
     * @param string $rawData - The original JSON response, before json_decode
     * @param Certificate $certificate - Override the auto-generated Certificate with your own
     * @param Application $application - Override the auto-generated Application with your own
     * @param \HTMLPurifier $purifier
     *
     * @throws \InvalidArgumentException - If the intent name or slots array is not present in the request
     */
    public function __construct(
        $rawData,
        Certificate $certificate,
        Application $application,
        \HTMLPurifier $purifier
    ) {
        // Parent construct
        parent::__construct($rawData, $certificate, $application, $purifier);

        // Require intent name
        if (!isset($this->getData()['request']['token'])) {
            throw new \InvalidArgumentException(self::ERROR_TOKEN_VALUE_NOT_SET);
        }

	    // Set token
	    $this->setToken($this->getData()['request']['token']);
    }

    // Accessors

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }


    // Mutators

    /**
     * @param string $intentName
     */
    protected function setToken($token)
    {
        $this->token = $this->purifier->purify((string)$token);
    }
}
