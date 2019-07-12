<?php

namespace avadim\Process;

use avadim\Process\ProcessShell\ShellAbstract;

/**
 * Class Process
 *
 * @package avadim\process
 */
class Process
{
    public static function getShellClass()
    {
        if (0 === stripos(PHP_OS, 'WIN')) {
            return 'avadim\\Process\\ProcessShell\\ShellWindows';
        }
        return 'avadim\\Process\\ProcessShell\\ShellUnix';
    }

    /**
     * Init the background process
     *
     * @param string|array $xCommand
     * @param array $aConfig
     *
     * @return ShellAbstract
     */
    public static function init($xCommand = '', $aConfig = [])
    {
        $sShellClass = self::getShellClass();

        return new $sShellClass($xCommand, $aConfig);
    }

    /**
     * Starts the background process and returns
     *
     * @param string $sCommand
     * @param array $aConfig
     *
     * @return bool|int
     */
    public static function start($sCommand, $aConfig = [])
    {
        $oManager = self::init($sCommand, $aConfig);

        return $oManager->start();
    }

    /**
     * Executes the process and waits for one to terminate
     *
     * @param string $sCommand
     * @param array $aConfig
     *
     * @return bool
     */
    public static function exec($sCommand, $aConfig = [])
    {
        $oManager = self::init($sCommand, $aConfig);

        return $oManager->exec();
    }

    /**
     * Checks if the process is currently running
     *
     * @param int $iPid
     *
     * @return bool
     */
    public static function isRunning($iPid)
    {
        $oManager = self::init();
        $oManager->setPid($iPid);

        return $oManager->isRunning();
    }

    /**
     * Kills the background process and its child processes
     *
     * @param int $iPid
     *
     * @return bool
     */
    public static function kill($iPid)
    {
        $oManager = self::init('');
        $oManager->setPid($iPid);

        return $oManager->kill();
    }

    /**
     * Init the php-script as a background process
     * The script must call the function ignore_user_abort(true) to continue in the background
     *
     * @param string|array $xCommand
     * @param array $aConfig
     *
     * @return ShellAbstract
     */
    public static function initPhp($xCommand, $aConfig = [])
    {
        $sPhp = self::phpBinary();
        if (!is_array($xCommand)) {
            $xCommand = [$sPhp, $xCommand];
        } else {
            array_unshift($xCommand, $sPhp);
        }

        return self::init($xCommand, $aConfig);
    }


    /**
     * Starts the php-script as a background process
     * The script must call the function ignore_user_abort(true) to continue in the background
     *
     * @param string|array $xCommand
     * @param array $aConfig
     *
     * @return bool|int
     */
    public static function startPhp($xCommand, $aConfig = [])
    {
        $oManager = self::initPhp($xCommand, $aConfig);

        return $oManager->start();
    }


    /**
     * Returns The PHP executable
     *
     * Simplified version of PhpExecutableFinder from Symfony\Component\Process
     * by Fabien Potencier <fabien@symfony.com> and Johannes M. Schmitt <schmittjoh@gmail.com>
     *
     * @param bool $includeArgs Whether or not include command arguments
     *
     * @return string|false The PHP executable path or false if it cannot be found
     */
    public static function phpBinary($includeArgs = true)
    {
        if ($php = getenv('PHP_BINARY')) {
            if (!is_executable($php)) {
                $command = '\\' === \DIRECTORY_SEPARATOR ? 'where' : 'command -v';
                if ($php = strtok(exec($command.' '.escapeshellarg($php)), PHP_EOL)) {
                    if (!is_executable($php)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            return $php;
        }

        if ($includeArgs && 'phpdbg' === \PHP_SAPI) {
            $args = '-qrr';
        } else {
            $args = '';
        }

        // PHP_BINARY return the current sapi executable
        if (PHP_BINARY && \in_array(\PHP_SAPI, ['cli', 'cli-server', 'phpdbg'], true)) {
            return PHP_BINARY.$args;
        }

        if ($php = getenv('PHP_PATH')) {
            if (!@is_executable($php)) {
                return false;
            }

            return $php;
        }

        if ($php = getenv('PHP_PEAR_PHP_BIN')) {
            if (@is_executable($php)) {
                return $php;
            }
        }

        if (@is_executable($php = PHP_BINDIR.('\\' === \DIRECTORY_SEPARATOR ? '\\php.exe' : '/php'))) {
            return $php;
        }

        return false;
    }

}

// EOF