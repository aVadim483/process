<?php

namespace avadim\Process\ProcessShell;

class ShellUnix extends ShellAbstract
{
    protected $iPriority = 0;
    protected $sOutFile = '/dev/null';

    /**
     * @param array $aConfig
     *
     * @return $this
     */
    public function setConfig($aConfig)
    {
        parent::setConfig($aConfig);
        if (isset($aConfig['priority'])) {
            $this->iPriority = (int)$aConfig['priority'];
        }
        return $this;
    }

    /**
     * @param string $sCommand
     *
     * @return int|bool
     */
    protected function startProcess($sCommand)
    {
        if($this->iPriority) {
            $sCmdLine = 'nohup nice -n ' . $this->iPriority . ' ' . $sCommand . ' & echo $!';
        } else {
            $sCmdLine = 'nohup ' . $sCommand . ' & echo $!';
        }
//echo print_r($sCmdLine, 1); exit;
        exec($sCmdLine, $aOutput, $iExitCode);
//echo print_r([$sCmdLine, $aOutput], 1); exit;
        if (!$iExitCode && !empty($aOutput)) {
            return reset($aOutput);
        }

        return false;
    }

    /**
     * Check if the process running
     *
     * @param int $iPID
     *
     * @return array
     */
    protected function getProcessInfo($iPID)
    {
        exec('ps -F -p ' . $iPID, $aOutput, $iExitCode);
        $aData = [];
        $aHeads = [];
        foreach($aOutput as $sLine) {
            if (!$aHeads) {
                $aHeads = explode(' ', preg_replace('/\s+/', ' ', $sLine));
            } else {
                $aLine = explode(' ', preg_replace('/\s+/', ' ', $sLine), count($aHeads));
                foreach ($aLine as $iKey => $sVal) {
                    if (isset($aHeads[$iKey])) {
                        $aData[$aHeads[$iKey]] = $sVal;
                    }
                }
                if (isset($aData['PID']) && $aData['PID'] == $iPID) {
                    return $aData;
                }
            }
        }
        return [];
    }

    /**
     * Check if the process running
     *
     * @param int $iPID
     *
     * @return bool
     */
    protected function isRunningProcess($iPID)
    {
        $aResult = $this->getProcessInfo($iPID);

        return !empty($aResult);
    }

    /**
     * Kill process
     *
     * @param int $iPID
     *
     * @return bool
     */
    protected function killProcess($iPID)
    {
        exec('kill -KILL ' . $iPID, $aOutput, $iExitCode);

        return !$this->isRunningProcess($iPID);
    }

}

// EOF