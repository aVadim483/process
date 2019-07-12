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
     * Returns task list in Windows as array (PID in keys)
     *
     * @param string $sOptions
     *
     * @return array
     */
    protected function _getWinTasks($sOptions)
    {
        $aResult = [];
        $iReturn = $this->_shellExec('tasklist ' . $sOptions . ' /FO CSV /V ', $aOutput);
        if (!$iReturn && !empty($aOutput[0]) && strpos($aOutput[0], ',') && false !== strpos($aOutput[0], '"')) {
            $aResult = [];
            $aData = [];
            $aHeads = [];
            foreach($aOutput as $sLine) {
                if ($this->sEncoding) {
                    $sLine = iconv($this->sEncoding, 'UTF-8', $sLine);
                }
                if (!$aHeads) {
                    $aHeads = str_getcsv($sLine);
                } else {
                    $aLine = str_getcsv($sLine);
                    foreach ($aLine as $iKey => $sVal) {
                        if (isset($aHeads[$iKey])) {
                            $aData[$aHeads[$iKey]] = $sVal;
                        }
                    }
                    if (isset($aData['PID'])) {
                        $aResult[(int)$aData['PID']] = $aData;
                    }
                }
            }
        }
        $aOutput = [];
        $sChkCmd = 'wmic process get CommandLine,ParentProcessId,ProcessId 2>&1';
        exec($sChkCmd, $aOutput, $iErrCode);
        if (!$iErrCode && $aOutput) {
            $aData = [];
            foreach($aOutput as $iKey => $sLine) {
                if ($iKey > 0) {
                    if ($this->sEncoding) {
                        $sLine = iconv($this->sEncoding, 'UTF-8', $sLine);
                    }
                    if (preg_match('/^(.*)\s+(\d+)\s+(\d+)$/', $sLine, $aM) && $aData['CMD'] = trim($aM[1])) {
                        $aData['PPID'] = (int)$aM[2];
                        $aData['PID'] = (int)$aM[3];
                        if (isset($aResult[$aData['PID']])) {
                            $aResult[$aData['PID']]['PPID'] = $aData['PPID'];
                            $aResult[$aData['PID']]['CMD'] = $aData['CMD'];
                        }
                    }
                }
            }
        }

        return $aResult;
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
        $aInfo = $this->_getWinTasks('/FI "PID eq ' . $iPID . '"');

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