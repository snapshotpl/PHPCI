<?php

namespace PHPCI\Logging;

use Psr\Log\LoggerInterface;

class Handler
{
    /**
     * @var array
     */
    protected $levels = array(
        E_WARNING           => 'Warning',
        E_NOTICE            => 'Notice',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated',
    );

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = NULL)
    {
        $this->logger = $logger;
    }

    public static function register(LoggerInterface $logger = NULL)
    {
        $handler = new static($logger);

        set_error_handler(array($handler, 'handleError'));
        register_shutdown_function(array($handler, 'handleFatalError'));

        set_exception_handler(array($handler, 'handleException'));
    }

    /**
     * @param integer $level
     * @param string  $message
     * @param string  $file
     * @param integer $line
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file, $line)
    {
        if (error_reporting() & $level) {

            $exception_level = isset($this->levels[$level]) ? $this->levels[$level] : $level;

            throw new \ErrorException(
                sprintf('%s: %s in %s line %d', $exception_level, $message, $file, $line),
                0, $level, $file, $line
            );
        }
    }

    /**
     * @throws \ErrorException
     */
    public function handleFatalError()
    {
        $fatal_error = error_get_last();

        try {
            if (($e = error_get_last()) !== null) {
                $e = new \ErrorException(
                    sprintf('%s: %s in %s line %d', $fatal_error['type'], $fatal_error['message'], $fatal_error['file'], $fatal_error['line']),
                    0, $fatal_error['type'], $fatal_error['file'], $fatal_error['line']
                );
                $this->log($e);
            }
        }
        catch (\Exception $e)
        {
            $e = new \ErrorException(
                sprintf('%s: %s in %s line %d', $fatal_error['type'], $fatal_error['message'], $fatal_error['file'], $fatal_error['line']),
                0, $fatal_error['type'], $fatal_error['file'], $fatal_error['line']
            );
            $this->log($e);
        }
    }

    /**
     * @param \Exception $exception
     */
    public function handleException(\Exception $exception)
    {
        $this->log($exception);
    }

    protected function log(\Exception $exception)
    {
        if (null !== $this->logger) {

            $message = sprintf(
                '%s: %s (uncaught exception) at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()
            );
            $this->logger->error($message, array('exception' => $exception));
        }
    }
}