<?php

namespace avadim\Process\ProcessShell;

abstract class ShellAbstract
{
    protected $aConfig;

    protected $sCommandApp;
    protected $aCommandOpt = [];
    protected $sCommand;
    protected $sOutFile;
    protected $sShellCommand;

    protected $iStartTime;
    protected $iPid;
    protected $sLabel;

    /**
     * Process constructor
     *
     * @param string|array $xCommand
     * @param string $sOutFile
     */
    public function __construct($xCommand = '', $sOutFile = null)
    {
        if (!is_array($xCommand)) {
            $this->sCommandApp = trim($xCommand);
            if (0 === strpos($this->sCommandApp, '"') && ($n = strpos($this->sCommandApp, '"', 1))) {
                $this->aCommandOpt = substr($this->sCommandApp, $n + 1);
                $this->sCommandApp = substr($this->sCommandApp, $n);
            }
        } else {
            foreach((array)$xCommand as $sCmd) {
                if (null === $this->sCommandApp) {
                    $this->sCommandApp = trim($sCmd);
                } else {
                    $this->aCommandOpt[] = trim($sCmd);
                }
            }
        }
        $this->sCommand = $this->sCommandApp;
        if ($this->aCommandOpt) {
            $this->sCommand .= ' ' . implode(' ', $this->aCommandOpt);
        }
        if ($sOutFile) {
            $this->sOutFile = $sOutFile;
        }
        $this->sLabel = uniqid(time() . '-', true);
    }

    /**
     * @param $aConfig
     *
     * @return $this
     */
    public function setConfig($aConfig)
    {
        $this->aConfig = $aConfig;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->sLabel;
    }

    /**
     * @param int $iPid
     */
    public function setPid($iPid)
    {
        $this->iPid = ((!empty($iPid) && is_numeric($iPid)) ? (int)$iPid : 0);
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->iPid;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->sCommand;
    }

    /**
     * Exec command in background
     *
     * @param string $sCommand
     *
     * @return int|bool
     */
    abstract protected function startProcess($sCommand);

    /**
     * Run Application in background
     *
     * @param string $sCommand
     *
     * @return int|bool
     */
    public function start($sCommand = null)
    {
        if ($sCommand) {
            $this->sCommand = $sCommand;
        }

        if (!$this->sCommand) {
            return false;
        }

        $sOutDev = ($this->sOutFile ? (' >' . $this->sOutFile) : '') . ' 2>&1';
        $sCmdLine = $this->sCommand . $sOutDev;
        $iPID = $this->startProcess($sCmdLine);

        $this->setPid($iPID);

        return $this->getPid();
    }

    /**
     * @param int $iPID
     *
     * @return bool
     */
    abstract protected function isRunningProcess($iPID);

    /**
     * @return bool|null
     */
    public function isRunning()
    {
        $iPID = $this->getPid();
        if ($iPID) {
            return $this->isRunningProcess($iPID);
        }
        return null;
    }

    abstract protected function getProcessInfo($iPID);

    public function getInfo()
    {
        $iPID = $this->getPid();
        if ($iPID) {
            return $this->getProcessInfo($iPID);
        }
        return null;
    }

    /**
     * @param int $iPID
     *
     * @return bool
     */
    abstract protected function killProcess($iPID);

    /**
     * Kill Application by PID
     *
     * @return bool|null
     */
    public function kill()
    {
        $iPid = $this->getPid();
        if ($iPid && $this->isRunning()) {
            return $this->killProcess($iPid);
        }
        return null;
    }

    /**
     * Execute command and wait
     *
     * @return bool
     */
    public function exec()
    {
        $iPid = $this->start();
        if ($iPid) {
            while($this->isRunning()) {
                sleep(1);
            }
            return true;
        }
        throw new \RuntimeException('It seems the command failed (pid is empty)');
    }

}

// EOF