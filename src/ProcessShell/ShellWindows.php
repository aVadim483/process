<?php

namespace avadim\Process\ProcessShell;

class ShellWindows extends ShellAbstract
{
    protected $sOutFile = 'NUL';
    protected $sEncoding;

    /**
     * ShellWindows constructor.
     *
     * @param string|array $xCommand
     * @param null $sOutFile
     */
    public function __construct($xCommand = '', $sOutFile = null)
    {
        parent::__construct($xCommand, $sOutFile);
        exec('chcp', $aOutput, $iResult);
        if (!$iResult && $aOutput) {
            $aWords = explode(' ', reset($aOutput));
            if ($sCodePage = end($aWords)) {
                $this->sEncoding = 'CP' . $sCodePage;
            }
        }
    }

    /**
     * @param $sCommand
     * @param $aOutput
     *
     * @return int
     */
    protected function _shellExec($sCommand, &$aOutput)
    {
        exec($sCommand . ' 2>&1', $aOutput, $iReturn);

        return $iReturn;
    }

    /**
     * Start process
     *
     * @param string $sCommand
     *
     * @return int|bool
     */
    protected function startProcess($sCommand)
    {
        $iPID = null;
        $sCmdLine = 'start /B ' . $sCommand . ' 2>&1';

        $aDescriptorSpec = [
            ['pipe', 'r'],
            ['pipe', 'w'],
        ];
        $aOptions = [
            'suppress_errors' => true,
        ];
        $this->sShellCommand = $sCmdLine;
        $hHandle = proc_open($sCmdLine, $aDescriptorSpec, $aPipes, null, null, $aOptions);
        if (is_resource($hHandle) && ($aData = proc_get_status($hHandle)) && isset($aData['pid'])) {
            $sChkCmd = 'wmic process get CommandLine,ParentProcessId,ProcessId 2>&1';
            exec($sChkCmd, $aOutput, $iErrCode);

            if (!$iErrCode) {
                foreach ((array)$aOutput as $sOutLine) {
                    if (0 === strpos($sOutLine, $this->sCommandApp)) {
                        $aOutput = array_filter(explode(' ', $sOutLine));
                        $iPID = end($aOutput);
                        break;
                    }
                }
            }
            foreach($aPipes as $xPipe) {
                fclose($xPipe);
            }
            proc_close($hHandle);
        }
        return $iPID;
    }

    /**
     * @param $iPID
     *
     * @return array
     */
    protected function getProcessInfo($iPID)
    {
        $aResult = [];
        $sCommand = 'wmic process where (processid=13496) get name,creationdate,commandline,processid,parentprocessid /value';
        exec($sCommand, $aOutput, $iErrorCode);
        $aKeys = [
            'CommandLine'       => 'CMD',
            'CreationDate'      => 'START',
            'Name'              => 'NAME',
            'ParentProcessId'   => 'PPID',
            'ProcessId'         => 'PID',
        ];
        if (!$iErrorCode && $aOutput) {
            $aOutput = array_map('trim', $aOutput);
            foreach($aOutput as $sLine) {
                if ($sLine) {
                    list($sParam, $sValue) = explode('=', $sLine);
                    if (isset($aKeys[$sParam])) {
                        $aResult[$aKeys[$sParam]] = $sValue;
                    }
                }
            }
        }
        return $aResult;
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
        $iReturn = $this->_shellExec('taskkill /F /T /PID ' . $iPID, $aOutput);

        return !$iReturn;
    }

}

// EOF