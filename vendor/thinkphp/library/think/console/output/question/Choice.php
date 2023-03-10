<?php
namespace think\console\output\question;
use think\console\output\Question;
class Choice extends Question
{
    private $choices;
    private $multiselect  = false;
    private $prompt       = ' > ';
    private $errorMessage = 'Value "%s" is invalid';
    public function __construct($question, array $choices, $default = null)
    {
        parent::__construct($question, $default);
        $this->choices = $choices;
        $this->setValidator($this->getDefaultValidator());
        $this->setAutocompleterValues($choices);
    }
    public function getChoices()
    {
        return $this->choices;
    }
    public function setMultiselect($multiselect)
    {
        $this->multiselect = $multiselect;
        $this->setValidator($this->getDefaultValidator());
        return $this;
    }
    public function isMultiselect()
    {
        return $this->multiselect;
    }
    public function getPrompt()
    {
        return $this->prompt;
    }
    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;
        return $this;
    }
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        $this->setValidator($this->getDefaultValidator());
        return $this;
    }
    private function getDefaultValidator()
    {
        $choices      = $this->choices;
        $errorMessage = $this->errorMessage;
        $multiselect  = $this->multiselect;
        $isAssoc      = $this->isAssoc($choices);
        return function ($selected) use ($choices, $errorMessage, $multiselect, $isAssoc) {
            $selectedChoices = str_replace(' ', '', $selected);
            if ($multiselect) {
                if (!preg_match('/^[a-zA-Z0-9_-]+(?:,[a-zA-Z0-9_-]+)*$/', $selectedChoices, $matches)) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $selected));
                }
                $selectedChoices = explode(',', $selectedChoices);
            } else {
                $selectedChoices = [$selected];
            }
            $multiselectChoices = [];
            foreach ($selectedChoices as $value) {
                $results = [];
                foreach ($choices as $key => $choice) {
                    if ($choice === $value) {
                        $results[] = $key;
                    }
                }
                if (count($results) > 1) {
                    throw new \InvalidArgumentException(sprintf('The provided answer is ambiguous. Value should be one of %s.', implode(' or ', $results)));
                }
                $result = array_search($value, $choices);
                if (!$isAssoc) {
                    if (!empty($result)) {
                        $result = $choices[$result];
                    } elseif (isset($choices[$value])) {
                        $result = $choices[$value];
                    }
                } elseif (empty($result) && array_key_exists($value, $choices)) {
                    $result = $value;
                }
                if (false === $result) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $value));
                }
                array_push($multiselectChoices, $result);
            }
            if ($multiselect) {
                return $multiselectChoices;
            }
            return current($multiselectChoices);
        };
    }
}
