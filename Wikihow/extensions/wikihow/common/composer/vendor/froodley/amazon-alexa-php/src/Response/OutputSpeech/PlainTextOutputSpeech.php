<?php

namespace Alexa\Response\OutputSpeech;

use Alexa\Response\OutputSpeech\OutputSpeechInterface;
use Alexa\Response\OutputSpeech\OutputSpeechTypes;

/**
 * Class PlainTextOutputSpeech
 *
 * Represents a PlainText OutputSpeech Alexa response object
 *
 * @package Alexa\Response\OutputSpeech
 */
class PlainTextOutputSpeech implements OutputSpeechInterface
{
    // Constants

    const ERROR_TEXT_NOT_SET  = 'You must provide output speech text.';

    // Fields

    /**
     * @var string
     */
    private $text;

    // Hooks

    /**
     * PlainTextOutputSpeech constructor.
     *
     * @param string $type
     * @param string $text
     */
    public function __construct($text)
    {
        $this->setText($text);
    }

    // Public Methods

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function render()
    {
        return [
            'type' => OutputSpeechTypes::TYPE_PLAIN_TEXT,
            'text' => $this->getText()
        ];
    }

    // Accessors

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    // Mutators

    /**
     * @param string $text
     */
    protected function setText($text)
    {
        if (!is_string($text)) {
            throw new \InvalidArgumentException(self::ERROR_TEXT_NOT_SET);
        }

        $this->text = $text;
    }
}
