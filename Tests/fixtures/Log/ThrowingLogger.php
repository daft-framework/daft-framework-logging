<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Log;

use Psr\Log\NullLogger as Base;
use RuntimeException;

class ThrowingLogger extends Base
{
    /**
    * @var int
    */
    protected $loggingCalls = 0;

    /**
    * @var int
    */
    protected $throwUnderLogCount = 0;

    /**
    * @var string
    */
    protected $exceptionMessage;

    /**
    * @var int
    */
    protected $exceptionCode = 0;

    public function __construct(
        int $throwUnderLogCount,
        string $exceptionMessage,
        int $exceptionCode = 0
    ) {
        $this->throwUnderLogCount = $throwUnderLogCount;

        $this->exceptionMessage = $exceptionMessage;
        $this->exceptionCode = $exceptionCode;
    }

    public function log($level, $message, array $context = []) : void
    {
        $this->loggingCalls += 1;

        if ($this->loggingCalls <= $this->throwUnderLogCount) {
            throw new RuntimeException($this->exceptionMessage, $this->exceptionCode);
        }

        parent::log($level, $message, $context);
    }
}
