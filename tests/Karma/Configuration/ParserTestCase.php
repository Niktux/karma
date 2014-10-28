<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\Parser;
use Karma\Logging\OutputInterfaceAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ParserTestCase extends \PHPUnit_Framework_TestCase
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

# Comments can be everywhere
[externals]
# Comments can be everywhere
externalFileNotFound.conf
                
[variables]
print_errors:
    prod, preprod = false
    default = true

# This is a comment
debug:
    dev = true
    default = false

@gourdin:
    # 0 for prod
    prod = 0
    # 1 for non-dev & non-prod envs
    preprod, recette, qualif = 1
            integration = null
    dev = 2
    staging = string with blanks

                # comment with bad indentation
#compressedComment
@tva:
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
    default = qd4#qs64d6q6=fgh4f6ùftgg==sdr
    
my.var.with.subnames:
    default = 21

param:
    dev = \${param}
    staging = Some\${nested}param
            
list.ok:
    dev = [ one, two, three]
    staging = [one,two]
    prod = [alone]
    preprod = not_a_list
    default = [ single value with blanks]
    other = [, 2, third]
    staging2 = []
    staging3 = [,,       ,  ,]

list.notlist:
    dev = string[weird
    staging = string]weird
    prod = [string[weird
    default = [string
    preprod = string]
    other = arr[]
    staging2 = arr[tung]
    staging3 = [1,2,3]4
    string1 = []]
    string2 = [[]
    string3 = [[]]
    string4 = [][]
    
variable-name-with-dashes:
    default = poney
    
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
            ->enableGroupSupport()
            ->setLogger(new OutputInterfaceAdapter(new ConsoleOutput(OutputInterface::VERBOSITY_QUIET)));
    }
}