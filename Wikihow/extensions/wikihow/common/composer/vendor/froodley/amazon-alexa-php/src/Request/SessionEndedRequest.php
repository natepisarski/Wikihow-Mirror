<?php

namespace Alexa\Request;

use Symfony\Component\Validator\Constraints as Assert;

use Alexa\Utility\Purifier\HasPurifier;
use Alexa\Request\BaseRequest;

/**
 * Class SessionEndedRequest
 *
 * Represents an Alexa SessionEndedRequest
 *
 * @package Alexa\Request
 */
class SessionEndedRequest extends BaseRequest
{
    // Traits

    use HasPurifier;

    // Fields

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    private $reason;

    // Hooks

    /**
     * SessionEndedRequest()
     *
     * @param string $rawData - The original JSON response, before json_decode
     * @param Certificate $certificate - Override the auto-generated Certificate with your own
     * @param Application $application - Override the auto-generated Application with your own
     * @param \HTMLPurifier $purifier
     */
    public function __construct(
        $rawData,
        Certificate $certificate,
        Application $application,
        \HTMLPurifier $purifier
    ) {
        // Parent construct
        parent::__construct($rawData, $certificate, $application, $purifier);

        // Set reason
        $this->setReason($this->getData()['request']['reason']);
    }

    // Accessors

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    // Mutators

    /**
     * @param string $reason
     */
    protected function setReason($reason)
    {
        $this->reason = $reason ? $this->purifier->purify((string)$reason) : null;
    }
}
