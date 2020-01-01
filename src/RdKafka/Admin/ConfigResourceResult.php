<?php

declare(strict_types=1);

namespace RdKafka\Admin;

use FFI;
use FFI\CData;
use RdKafka\Api;

class ConfigResourceResult extends Api
{
    public string $name;
    public int $type;
    public int $error;
    public ?string $errorString;

    /**
     * @var ConfigEntry[]
     */
    public array $configs;

    public function __construct(CData $result)
    {
        parent::__construct();

        $this->name = FFI::string(self::$ffi->rd_kafka_ConfigResource_name($result));
        $this->type = (int) self::$ffi->rd_kafka_ConfigResource_type($result);
        $this->error = (int) self::$ffi->rd_kafka_ConfigResource_error($result);
        $errorStringCdata = self::$ffi->rd_kafka_ConfigResource_error_string($result);
        $this->errorString = $errorStringCdata === null ? null : FFI::string($errorStringCdata);

        $size = FFI::new('size_t');
        $configsPtr = self::$ffi->rd_kafka_ConfigResource_configs($result, FFI::addr($size));
        $configs = [];
        for ($i = 0; $i < (int) $size->cdata; $i++) {
            $configs[] = new ConfigEntry($configsPtr[$i]);
        }
        $this->configs = $configs;
    }
}
