<?php

namespace Ms3\Ms3CommerceFx\EventListener;

class BeforeFileProcessingEvent
{
    public function __invoke(\TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent $event)
    {
        $origFile = $event->getFile();
        if (strpos($origFile->getIdentifier(), '/Graphics/') === 0) {
            // $origFile->getModificationTime() will report the DB store date, not actual file date
            // get real path and real modification time
            $path = $origFile->getForLocalProcessing(false);
            $origMtime = filemtime($path);

            // if physical file is newer, check if process file is up to date
            if ($origMtime !== false && $origFile->getModificationTime() < $origMtime) {
                $processFile = $event->getProcessedFile();
                if ($processFile->exists()) {
                    $path = $processFile->getForLocalProcessing(false);
                    $procMtime = filemtime($path);
                    if ($procMtime !== false && $procMtime < $origMtime) {
                        $processFile->delete(true);
                    }
                }
            }
        }
    }
}