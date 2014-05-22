[1mdiff --git a/src/Karma/Hydrator.php b/src/Karma/Hydrator.php[m
[1mindex ceed8c6..71abb90 100644[m
[1m--- a/src/Karma/Hydrator.php[m
[1m+++ b/src/Karma/Hydrator.php[m
[36m@@ -21,7 +21,8 @@[m [mclass Hydrator[m
         $enableBackup,[m
         $finder,[m
         $formatterProvider,[m
[31m-        $currentFormatterName;[m
[32m+[m[32m        $currentFormatterName,[m
[32m+[m[32m        $currentTargetFile;[m
     [m
     public function __construct(Filesystem $sources, Configuration $reader, Finder $finder, FormatterProvider $formatterProvider = null)[m
     {[m
[36m@@ -42,6 +43,7 @@[m [mclass Hydrator[m
         }[m
         [m
         $this->currentFormatterName = null;[m
[32m+[m[32m        $this->currentTargetFile = null;[m
     }[m
 [m
     public function setSuffix($suffix)[m
[36m@@ -94,18 +96,19 @@[m [mclass Hydrator[m
     [m
     private function hydrateFile($file, $environment)[m
     {[m
[32m+[m[32m        $this->currentTargetFile = substr($file, 0, strlen($this->suffix) * -1);[m
[32m+[m[41m        [m
         $content = $this->sources->read($file);[m
         $content = $this->parseFileDirectives($file, $content);[m
         [m
         $targetContent = $this->injectValues($file, $content, $environment);[m
         [m
[31m-        $targetFile = substr($file, 0, strlen($this->suffix) * -1);[m
[31m-        $this->debug("Write $targetFile");[m
[32m+[m[32m        $this->debug("Write $this->currentTargetFile");[m
 [m
         if($this->dryRun === false)[m
         {[m
[31m-            $this->backupFile($targetFile);[m
[31m-            $this->sources->write($targetFile, $targetContent, true);[m
[32m+[m[32m            $this->backupFile($this->currentTargetFile);[m
[32m+[m[32m            $this->sources->write($this->currentTargetFile, $targetContent, true);[m
         }[m
     }[m
     [m
[36m@@ -152,7 +155,7 @@[m [mclass Hydrator[m
     [m
     private function injectScalarValues(& $content, $environment)[m
     {[m
[31m-        $fileExtension = null; // FIXME[m
[32m+[m[32m        $fileExtension = pathinfo($this->currentTargetFile, PATHINFO_EXTENSION);[m
         $formatter = $this->formatterProvider->getFormatter($fileExtension, $this->currentFormatterName);[m
         [m
         $content = preg_replace_callback(self::VARIABLE_REGEX, function(array $matches) use($environment, $formatter)[m
[36m@@ -174,7 +177,7 @@[m [mclass Hydrator[m
     [m
     private function injectListValues(& $content, $environment)[m
     {[m
[31m-        $fileExtension = null; // FIXME[m
[32m+[m[32m        $fileExtension = pathinfo($this->currentTargetFile, PATHINFO_EXTENSION);[m
         $formatter = $this->formatterProvider->getFormatter($fileExtension, $this->currentFormatterName);[m
         $replacementCounter = 0;[m
         [m
