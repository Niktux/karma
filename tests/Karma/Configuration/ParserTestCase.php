<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\Parser;
use Karma\Logging\OutputInterfaceAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ParserTestCase extends \PHPUnit_Framework_TestCase
{
    const
        MASTERFILE_PATH = 'master.conf';
    
    protected
        $parser,
        $variables;
    
    protected function setUp()
    {
        $contentMaster = <<<CONFFILE
[includes]
db.conf

[variables]
print_errors:
    prod, preprod = false
    default = true
      
debug:
    dev = true
    default = false

gourdin:
    prod = 0
    preprod, recette, qualif = 1
            integration = null
    dev = 2

tva:
        dev =   19.0
        preprod = 20.50
    default=19.6            
server:
    prod = sql21
    preprod = prod21
    recette, qualif = rec21
    
apiKey:
    recette =
    dev==2
    default = qd4qs64d6q6=fgh4f6ùftgg==sdr
    
my.var.with.subnames:
    default = 21    
    
CONFFILE;

        $contentDb = <<< CONFFILE
[includes]
master.conf
db.conf

[variables]
user:
    default = root        
CONFFILE;
        
        $files = array(
            self::MASTERFILE_PATH => $contentMaster,
            'db.conf' => $contentDb,
        );
        
        $adapter = new InMemory($files);
        $fs = new Filesystem($adapter);
        
        $this->parser = new Parser($fs);
        $this->parser
            ->enableIncludeSupport()
            ->enableExternalSupport()
            ->setLogger(new OutputInterfaceAdapter(new ConsoleOutput(OutputInterface::VERBOSITY_QUIET)));
    }
}