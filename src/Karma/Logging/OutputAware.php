<?php

namespace Karma\Logging;

use Symfony\Component\Console\Output\OutputInterface;

trait OutputAware
{
    private
        $output = null;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        
        return $this;
    }
    
    protected function debug($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        if($this->output instanceof OutputInterface)
        {
            if(OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity())
            {
                if(! is_array($messages))
                {
                    $messages = array($messages);
                }
    
                array_walk($messages, function(& $message){
                    $message = "<fg=cyan>$message</fg=cyan>";
                });
    
                    $this->output->write($messages, $newline, $type);
            }
        }
    }
}