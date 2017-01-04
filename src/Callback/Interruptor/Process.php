<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\Callback\Interruptor;

use Opis\Closure\SerializableClosure;
use zaboy\Callback\CallbackException;
use zaboy\Callback\Callback;
use zaboy\Callback\InterruptorInterface;
use zaboy\Callback\Interruptor\Job;

/**
 * AnotherProcess
 *
 * @category   callback
 * @package    zaboy
 */
class Process extends Callback implements InterruptorInterface
{

    const CALLBACK_KEY = 'callback';
    const VALUE_KEY = 'value';
    //
    const STDOUT_KEY = 'stdout';
    const STDERR_KEY = 'stderr';
    const PID_KEY = 'pid';
    const SERVICE_MACHINE_NAME_KEY = 'SERVICE_MACHINE_NAME';
    const INTERRUPTOR_TYPE_KEY = 'interruptor_type_key';
    //
    const PATH_SCRIPT_SRC = 'src/Callback/Interruptor/Script/';
    const PATH_SCRIPT_WWW = 'www/Callback/Interruptor/Script/';
    const FILE_NAME = 'process.php';

    public function __invoke($value)
    {
        if (!is_file($this->getScriptName())) {
            throw new CallbackException('Sript "' . $this->getScriptName() . '" does not exist in the folder "Script"');
        }
        $cmd = 'php ' . $this->getScriptName();

        $job = new Job($this->getCallback(), $value);

        $serializedJob = $job->serializeBase64();
        $cmd .= ' ' . $serializedJob;

        // Files names for stdout and stderr
        $result[self::STDOUT_KEY] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('stdout_', 1);
        $result[self::STDERR_KEY] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('stderr_', 1);
        $result[Process::INTERRUPTOR_TYPE_KEY] = static::class;
        $cmd .= "  1>{$result[self::STDOUT_KEY]} 2>{$result[self::STDERR_KEY]}";

        if (substr(php_uname(), 0, 7) !== "Windows") {
            $cmd .= " & echo $!";
        }

        //from apache - $command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
        $result[self::PID_KEY] = trim(shell_exec($cmd));
        $result[strtolower(self::SERVICE_MACHINE_NAME_KEY)] = getenv(self::SERVICE_MACHINE_NAME_KEY);
        return $result;

//        $errors = $this->parser->parseFile($stdErrFilename);
//        $output = $this->parser->parseFile($stdOutFilename);
//
//        if ($errors['fatalStatus']) {
//            throw new CallbackException($errors['message']);
//        }
//        return $output['message'];
    }

    protected function getScriptName()
    {
        if (!file_exists(self::PATH_SCRIPT_WWW . self::FILE_NAME)) {
            throw new \RuntimeException(
            'File ' . self::FILE_NAME, ' is not exist in ' . self::PATH_SCRIPT_WWW
            );
        }
        return self::PATH_SCRIPT_WWW . self::FILE_NAME;
    }

    /**
     * Checks an environment where this script was run
     *
     * It's not allowed to run in Windows
     *
     * @throws CallbackException
     */
    protected function checkEnvironment()
    {
        if ('Windows' == substr(php_uname(), 0, 7)) {
            throw new CallbackException("This callback type will not work in Windows");
        }
        if (!function_exists('shell_exec')) {
            throw new CallbackException("The function \"shell_exec\" does not exist or it is not allowed.");
        }
        if (!function_exists('posix_kill')) {
            throw new CallbackException("The function \"posix_kill\" does not exist or it is not allowed.");
        }
    }

    /**
     * @param callable $callback
     */
    protected function setCallback(callable $callback)
    {
        $callback = $callback instanceof \Closure ? new SerializableClosure($callback) : $callback;
        parent::setCallback($callback);
    }


}
